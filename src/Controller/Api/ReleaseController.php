<?php

namespace App\Controller\Api;

use App\Entity\Artist;
use App\Entity\Release;
use App\Service\MusicBrainzService;
use App\Repository\ArtistRepository;
use App\Repository\ReleaseRepository;
use App\Service\CoverArtArchiveService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/release', name: 'api.release.')]
final class ReleaseController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $client,
        private SluggerInterface $slugger
    ) {}

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->json([
            'type' => 'success',
            'message' => 'LibTrack API is running.',
        ]);
    }

    #[Route('/scan', name: 'scan', methods: ['POST'])]
    // #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function scan(
        Request $request,
        MusicBrainzService $musicBrainzService,
        CoverArtArchiveService $coverService
    ): Response {
        // if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
        //     return $this->json([
        //         'type' => 'error',
        //         'message' => 'You need to be logged in to access this resource.'
        //     ], Response::HTTP_UNAUTHORIZED);
        // }

        $barcode = $request->toArray();
        $barcode = $barcode['barcode'];
        $releases = null;

        if (!$barcode) {
            return $this->json([
                'type' => 'error',
                'message' => 'Barcode is required'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $releaseData = $musicBrainzService->getReleaseByBarcode($barcode);
            $releases = $releaseData["releases"];
        } catch (\Exception $e) {
            return $this->json([
                'type' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }

        // Attach cover art
        foreach ($releases as $key => $release) {
            $release['cover'] = $coverService->getCoverArtByMbid($release['id']);
            $releases[$key] = $release;
        }

        return $this->json([
            'type' => 'success',
            'barcode' => $barcode,
            'releases' => $releases,
            'message' => 'Available releases for the barcode: ' . $barcode,
        ]);
    }

    #[Route('/scan/add', name: 'scan.add', methods: ['POST'])]
    public function scanAdd(
        Request $request,
        EntityManagerInterface $em,
        ReleaseRepository $releaseRepository,
        ArtistRepository $artistRepository,
        MusicBrainzService $musicBrainzService
    ): Response {
        // Get the JSON payload
        $data = json_decode($request->getContent(), true);
        $releaseId = $data['release_id'] ?? null;
        $barcode = $data['barcode'] ?? null;

        if (!$releaseId) {
            return $this->json([
                'type' => 'error',
                'message' => 'No release ID provided'
            ], 400);
        }

        // Fetch the complete release data
        try {
            $releaseData = $musicBrainzService->getReleaseWithCoverArt($releaseId);
        } catch (\Exception $e) {
            return $this->json([
                'type' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }

        // Check if the release does NOT already exist
        $checkRelease = $releaseRepository->findOneBy(['barcode' => $barcode]);

        if ($checkRelease) {
            return $this->json([
                'type' => 'warning',
                'message' => 'Barcode "' . $barcode . '" already in the database.'
            ]);
        }

        $release = new Release();
        $release->setTitle($releaseData['title']);
        $release->setBarcode($barcode);

        if ($releaseData['cover']) {
            $release->setCover($releaseData['cover']);
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
                    $artist = $artistRepository->findOneBy(['name' => $artistData['name']]);

                    if (!$artist) {
                        $artist = new Artist();
                        $artist->setName($artistData['name']);
                        $artistSlug = $this->slugger->slug(strtolower($artistData['name']));
                        $artist->setSlug($artistSlug);
                        $artist->setCreatedAt($now);
                        $artist->setUpdatedAt($now);

                        $em->persist($artist);
                    }

                    $release->addArtist($artist);
                }
            }
        }

        $em->persist($release);
        $em->flush();

        return $this->json([
            'type' => 'success',
            'message' => 'Release "' . $release->getTitle() . '" added successfully'
        ]);
    }
}
