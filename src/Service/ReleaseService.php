<?php

namespace App\Service;

use App\Entity\Artist;
use App\Entity\Release;
use App\Repository\ArtistRepository;

use App\Repository\ReleaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ReleaseService
{
    private $coverDir;

    public function __construct(
        private ParameterBagInterface $params,
        private SluggerInterface $slugger,
        private EntityManagerInterface $em,
        private DiscogsService $discogsService,
        private ReleaseRepository $releaseRepository,
        private ArtistRepository $artistRepository,
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
            throw new NotFoundHttpException('Release data not found.', $e);
        }

        // Check if the release does NOT already exist
        $checkRelease = $this->releaseRepository->findOneBy(['barcode' => $barcode]);

        if ($checkRelease) {
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
}
