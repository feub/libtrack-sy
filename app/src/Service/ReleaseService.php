<?php

namespace App\Service;

use App\Entity\Artist;
use App\Entity\Release;
use Psr\Log\LoggerInterface;
use App\Repository\ArtistRepository;
use App\Repository\ReleaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
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
        private Security $security
    ) {
        $this->coverDir = $params->get('cover_dir');
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

    public function manualAddRelease(
        array $data,
    ) {
        if (!isset($data['barcode'])) {
            $this->logger->error('Barcode is missing');
            throw new BadRequestHttpException('Barcode is missing');
        }

        // Check if the release does NOT already exist
        $checkRelease = $this->releaseRepository->findOneBy(['barcode' => $data['barcode']]);

        if ($checkRelease) {
            $this->logger->error('Barcode "' . $data['barcode'] . '" already exists.');
            throw new BadRequestHttpException('Barcode "' . $data['barcode'] . '" already exists.');
        }

        $release = new Release();
        $release->setTitle($data['title']);
        $release->setBarcode($data['barcode']);

        if ($data['release_date']) {
            $release->setReleaseDate($data['release_date']);
        }

        if ($data['cover']) {
            $release->setCover($data['cover']);
        }

        // Slug
        $slug = $this->slugger->slug(strtolower($data['title'] . '-' . $data['barcode'] . '-' . rand(10000, 79999)));
        $release->setSlug($slug);

        // Timestamps
        $now = new \DateTimeImmutable();
        $release->setCreatedAt($now);
        $release->setUpdatedAt($now);

        // Artist
        if (isset($data['artists'])) {
            $artists = explode(',', $data['artists']);
            foreach ($artists as $artistName) {
                $name = $this->stripArtistName($artistName);

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

        // Format
        if (isset($data['format']) && isset($data['format']['id'])) {
            $formatRepository = $this->em->getRepository(\App\Entity\Format::class);
            $format = $formatRepository->find($data['format']['id']);

            if ($format) {
                $release->setFormat($format);
            }
        }

        // Shelf
        if (isset($data['shelf']) && isset($data['shelf']['id'])) {
            $shelfRepository = $this->em->getRepository(\App\Entity\Shelf::class);
            $shelf = $shelfRepository->find($data['shelf']['id']);

            if ($shelf) {
                $release->setShelf($shelf);
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

    /**
     * Check if the current user can delete the release
     *
     * @return bool True if the user can delete the release
     */
    private function canDelete(): bool
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        return false;
    }

    public function deleteRelease(Release $release): void
    {
        if (!$release) {
            throw new NotFoundHttpException('Release not found');
        }

        // User does not have permission
        if (!$this->canDelete($release)) {
            throw new AccessDeniedException('You are not allowed to delete this release');
        }

        $coverPath = $this->coverDir . $release->getCover();

        try {
            $this->em->remove($release);
            $this->em->flush();

            if ($coverPath && file_exists($coverPath)) {
                if (!unlink($coverPath)) {
                    $this->logger->error("Failed to delete the cover file at path: $coverPath");
                }
            }
        } catch (\Exception $e) {
            $this->logger->error("An error occurred while deleting the release: " . $e->getMessage());
            throw new \RuntimeException("Failed to delete the release: " . $e->getMessage());
        }
    }
}
