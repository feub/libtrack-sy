<?php

namespace App\Service;

use App\Entity\Artist;
use App\Entity\Release;
use Psr\Log\LoggerInterface;
use App\Repository\ArtistRepository;
use App\Repository\ReleaseRepository;
use App\Dto\ReleaseDto;
use App\Mapper\ReleaseDtoMapper;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ReleaseService
{
    private $coverDir;

    public function __construct(
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
        private SluggerInterface $slugger,
        private EntityManagerInterface $em,
        private DiscogsService $discogsService,
        private ReleaseRepository $releaseRepository,
        private ArtistRepository $artistRepository,
        private Security $security,
        private NormalizerInterface $serializer,
        private ReleaseDtoMapper $releaseDtoMapper
    ) {
        $this->coverDir = $params->get('cover_dir');
    }

    /**
     * Get paginated releases
     *
     * @param int $page Current page number
     * @param int $limit Number of items per page
     * @param string $searchTerm Optional search term
     * @return array Array containing releases data, total releases, max page and current page
     */
    public function getPaginatedReleases(int $page = 1, int $limit = 20, string $searchTerm = ''): array
    {
        $totalReleases = $this->releaseRepository->getTotalReleases();
        $releases = $this->releaseRepository->paginatedReleases($page, $limit, $searchTerm);

        if (!empty($searchTerm)) {
            $totalReleases = $releases->count();
        }

        $maxpage = ceil($totalReleases / $limit);

        return [
            'releases' => $releases->getItems(),
            'totalReleases' => $totalReleases,
            'maxPage' => $maxpage,
            'page' => $page
        ];
    }

    /**
     * Get formated release data
     *
     * @param int $id Release ID
     * @return array Formated release data
     * @throws NotFoundHttpException If the release does not exist
     */
    public function getReleaseFormatted(int $id): array
    {
        $release = $this->releaseRepository->getRelease($id);

        if (!$release) {
            throw new NotFoundHttpException('Release not found');
        }

        return $this->serializer->normalize(
            $release,
            null,
            [
                'circular_reference_handler' => function ($object) {
                    return $object->getId();
                },
                'groups' => ['api.release.list']
            ]
        );
    }

    // Create release from DTO
    public function createFromDto(ReleaseDto $dto): Release
    {
        // Check if the release already exists
        if ($this->releaseRepository->findOneBy(['barcode' => $dto->barcode])) {
            $this->logger->error('Barcode "' . $dto->barcode . '" already exists.');
            throw new BadRequestHttpException('Barcode "' . $dto->barcode . '" already exists.');
        }

        // Create a new release entity from the DTO
        $release = $this->releaseDtoMapper->createEntityFromDto($dto);

        // Persist and flush
        $this->em->persist($release);
        $this->em->flush();

        return $release;
    }

    // Update from DTO
    public function updateFromDto(Release $release, ReleaseDto $dto): Release
    {
        // Update the release from the DTO
        $this->releaseDtoMapper->updateEntityFromDto($dto, $release);

        $this->em->flush();

        return $release;
    }

    public function downloadCovertArt(string $coverUrl, string $id): string
    {
        if (!is_dir($this->coverDir)) {
            mkdir($this->coverDir, 0775, true);
        }

        $coverPath = $this->coverDir . $id . '.jpg';
        $coverContent = file_get_contents($coverUrl);
        file_put_contents($coverPath, $coverContent);

        return $id . '.jpg';
    }

    public function addRelease(
        string $releaseId,
        string $barcode,
    ) {
        // Fetch the complete release data
        try {
            $releaseData = $this->discogsService->getReleaseById($releaseId);
        } catch (\Exception $e) {
            $this->logger->error("Release data not found: $e");
            throw new NotFoundHttpException('Release data not found.', $e);
        }

        // Check if the release does NOT already exist
        $checkRelease = $this->releaseRepository->findOneBy(['barcode' => $barcode]);

        if ($checkRelease) {
            $this->logger->error('Barcode "' . $barcode . '" already exists.');
            throw new BadRequestHttpException('Barcode "' . $barcode . '" already exists.');
        }

        $release = new Release();
        $release->setTitle($releaseData['title']);
        $release->setBarcode($barcode);

        if ($releaseData['year']) {
            $release->setReleaseDate($releaseData['year']);
        }

        if ($releaseData['images'][0]['uri']) {
            $coverPath = $this->downloadCovertArt($releaseData['images'][0]['uri'], $releaseId);
            $release->setCover($coverPath);
        }

        // Slug
        $slug = $this->slugger->slug(strtolower($releaseData['title'] . '-' . $barcode . '-' . $releaseId));
        $release->setSlug($slug);

        // Timestamps
        $now = new \DateTimeImmutable();
        $release->setCreatedAt($now);
        $release->setUpdatedAt($now);

        // Artist
        if (isset($releaseData['artists'])) {
            foreach ($releaseData['artists'] as $artistData) {
                if (isset($artistData['name'])) {
                    $name = $this->stripArtistName($artistData['name']);

                    // Check if artist already exists
                    $artist = $this->artistRepository->findOneBy(['name' => $name]);

                    if (!$artist) {
                        $artist = new Artist();
                        $artist->setName($name);
                        $artistSlug = $this->slugger->slug(strtolower($name));
                        $artist->setSlug($artistSlug);
                        $artist->setCreatedAt($now);
                        $artist->setUpdatedAt($now);

                        $this->em->persist($artist);
                    }

                    $release->addArtist($artist);
                }
            }
        }

        $this->em->persist($release);
        $this->em->flush();

        return $release;
    }

    private function stripArtistName(string $artistName): string
    {
        return preg_replace('/\s*\(\d+\)$/', '', $artistName);
    }

    public function deleteRelease(Release $release): void
    {
        if (!$release) {
            throw new NotFoundHttpException('Release not found');
        }

        try {
            $this->em->remove($release);
            $this->em->flush();

            $coverPath = $this->coverDir . $release->getCover();

            if ($release->getCover() && file_exists($coverPath)) {
                if (!unlink($coverPath)) {
                    $this->logger->error("Failed to delete the cover file at path: $coverPath");
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("An error occurred while deleting the release: " . $e->getMessage());
            throw new \RuntimeException("Failed to delete the release: " . $e->getMessage());
        }
    }

    /**
     * Get a single release
     *
     * @param int $id ID of the release
     * @return ?Release Release containing the release data or null
     */
    public function getRelease(int $id): ?Release
    {
        $release = $this->releaseRepository->getRelease($id);

        return $release;
    }
}
