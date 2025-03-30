<?php

namespace App\Service;

use App\Entity\Artist;
use App\Entity\Release;
use App\Repository\ArtistRepository;

use App\Repository\ReleaseRepository;
use Doctrine\ORM\EntityManagerInterface;
use function PHPUnit\Framework\throwException;
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
    private MusicBrainzService $musicBrainzService,
    private ReleaseRepository $releaseRepository,
    private ArtistRepository $artistRepository,
  ) {
    $this->coverDir = $params->get('cover_dir');
  }

  public function downloadCovertArt(string $coverUrl, string $mbid): string
  {
    if (!is_dir($this->coverDir)) {
      mkdir($this->coverDir, 0775, true);
    }

    $coverPath = $this->coverDir . $mbid . '.jpg';
    $coverContent = file_get_contents($coverUrl);
    file_put_contents($coverPath, $coverContent);

    return $mbid . '.jpg';
  }

  public function addRelease(
    string $releaseId,
    string $barcode,
  ) {
    // Fetch the complete release data
    try {
      $releaseData = $this->musicBrainzService->getReleaseWithCoverArt($releaseId);
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

    if ($releaseData['cover']) {
      $coverPath = $this->downloadCovertArt($releaseData['cover'], $releaseId);
      $release->setCover($coverPath);
    }

    // Release date (extract year if available)
    if (isset($releaseData['date']) && strlen($releaseData['date']) >= 4) {
      $yearString = substr($releaseData['date'], 0, 4);
      $year = (int)$yearString;
      $release->setReleaseDate($year);
    }

    // Slug
    $slug = $this->slugger->slug(strtolower($releaseData['title'] . '-' . $barcode . '-' . $releaseId));
    $release->setSlug($slug);

    // Timestamps
    $now = new \DateTimeImmutable();
    $release->setCreatedAt($now);
    $release->setUpdatedAt($now);

    // Artist
    if (isset($releaseData['artist-credit'])) {
      foreach ($releaseData['artist-credit'] as $artistCredit) {
        if (isset($artistCredit['artist'])) {
          $artistData = $artistCredit['artist'];

          // Check if artist already exists
          $artist = $this->artistRepository->findOneBy(['name' => $artistData['name']]);

          if (!$artist) {
            $artist = new Artist();
            $artist->setName($artistData['name']);
            $artistSlug = $this->slugger->slug(strtolower($artistData['name']));
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
}
