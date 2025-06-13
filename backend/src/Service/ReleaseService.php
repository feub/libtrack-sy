<?php

namespace App\Service;

use App\Entity\Genre;
use App\Entity\Artist;
use App\Dto\ReleaseDto;
use App\Entity\Release;
use Psr\Log\LoggerInterface;
use App\Mapper\ReleaseDtoMapper;
use App\Repository\GenreRepository;
use App\Repository\ArtistRepository;
use App\Repository\ReleaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

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
        private GenreRepository $genreRepository,
        private Security $security,
        private NormalizerInterface $serializer,
        private ReleaseDtoMapper $releaseDtoMapper,
        private ValidatorInterface $validator
    ) {
        $this->coverDir = $params->get('cover_dir');
    }

    /**
     * Get paginated releases
     *
     * @param int $page Current page number
     * @param int $limit Number of items per page
     * @param string $searchTerm Optional search term
     * @return array Array containing releases data, total, max page and current page
     */
    public function getPaginatedReleases(int $page = 1, int $limit = 20, ?string $searchTerm = '', ?string $searchShelf = ''): array
    {
        $totalReleases = $this->releaseRepository->getTotalReleases();
        $releases = $this->releaseRepository->paginatedReleases($page, $limit, $searchTerm, $searchShelf);

        if (!empty($searchTerm) || !empty($searchShelf)) {
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

        // $coverContent = file_get_contents($coverUrl);
        // file_put_contents($coverPath, $coverContent);

        // Use cURL instead of file_get_contents
        $ch = curl_init($coverUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $coverContent = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->logger->error('Cover download failed: ' . curl_error($ch));
            curl_close($ch);
            return '';
        }

        curl_close($ch);
        file_put_contents($coverPath, $coverContent);

        return $id . '.jpg';
    }

    public function setFormat(string $format): ?array
    {
        if ($format === "Vinyl") {
            return ['id' => 2];
        } elseif ($format === "CD") {
            return ['id' => 1];
        } else {
            return null;
        }
    }

    public function addScannedRelease(
        string $releaseId,
        string $barcode,
        ?int $shelf
    ) {
        // Check if the release does NOT already exist
        if ($this->releaseRepository->findOneBy(['barcode' => $barcode])) {
            $this->logger->error('Barcode "' . $barcode . '" already exists.');
            // ConflictHttpException: status 409 (conflict)
            throw new ConflictHttpException('Barcode "' . $barcode . '" already exists.');
        }

        // Fetch the complete release data
        try {
            $releaseData = $this->discogsService->getReleaseById($releaseId);
            $formattedData = [
                'title' => $releaseData['title'] ?? null,
                'release_date' => $releaseData['year'] <= 1000 ? null : $releaseData['year'],
                'barcode' => $barcode,
                'cover' => $releaseData['images'][0]['uri'] ? $this->downloadCovertArt($releaseData['images'][0]['uri'], $releaseData['id']) : null,
                'artists' => array_map(function ($artist) {
                    return ['id' => $this->getArtistIdByName($artist['name'])];
                }, $releaseData['artists'] ?? []),
                'format' => $releaseData['formats'][0]["name"] ? $this->setFormat($releaseData['formats'][0]["name"]) : null,
                'shelf' => ["id" => $shelf] ?? null,
                'genres' => array_map(function ($style) {
                    return ['id' => $this->getGenreIdByName($style)];
                }, $releaseData['styles'] ?? []),
            ];
        } catch (\Exception $e) {
            $this->logger->error("Release data not found: $e");
            throw new NotFoundHttpException('Release data not found.', $e);
        }

        // Create and validate DTO
        $releaseDto = ReleaseDto::fromArray($formattedData);
        $violations = $this->validator->validate($releaseDto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            throw new BadRequestHttpException('Validation failed: ' . json_encode($errors));
        }

        // Create the release
        $release = $this->createFromDto($releaseDto);

        return $release;
    }

    private function stripArtistName(string $artistName): string
    {
        return preg_replace('/\s*\(\d+\)$/', '', $artistName);
    }

    /**
     * Find or create an artist by name and return its ID
     *
     * @param string $artistName The name of the artist to find or create
     * @return int The ID of the found or created artist
     */
    private function getArtistIdByName(string $artistName): int
    {
        $cleanName = $this->stripArtistName($artistName);

        $artist = $this->artistRepository->findOneBy(['name' => $cleanName]);

        if (!$artist) {
            $artist = new Artist;
            $artist->setName($cleanName);

            $slug = $this->slugger->slug(strtolower($cleanName))->toString();
            $artist->setSlug($slug);

            $now = new \DateTimeImmutable();
            $artist->setCreatedAt($now);
            $artist->setUpdatedAt($now);

            $this->em->persist($artist);
            $this->em->flush();

            $this->logger->info("Created new artist: $cleanName");
        }

        return $artist->getId();
    }

    /**
     * Find or create a genre by name and return its ID
     *
     * @param string $genreName The name of the genre to find or create
     * @return int The ID of the found or created genre
     */
    private function getGenreIdByName(string $genreName): int
    {
        $genre = $this->genreRepository->findOneBy(['name' => $genreName]);

        if (!$genre) {
            $genre = new Genre;
            $genre->setName($genreName);

            $slug = $this->slugger->slug(strtolower($genreName))->toString();
            $genre->setSlug($slug);

            $this->em->persist($genre);
            $this->em->flush();

            $this->logger->info("Created new genre: $genreName");
        }

        return $genre->getId();
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
