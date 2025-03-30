<?php

namespace App\Controller\Api;

use App\Entity\Artist;
use App\Entity\Release;
use App\Service\ReleaseService;
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
        ReleaseService $releaseService
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

        if (!$barcode) {
            return $this->json([
                'type' => 'error',
                'message' => 'No barcode provided'
            ], 400);
        }

        try {
            $release = $releaseService->addRelease($releaseId, $barcode);
        } catch (\Exception $e) {
            return $this->json([
                'type' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }

        return $this->json([
            'type' => 'success',
            'message' => 'Release "' . $release->getTitle() . '" added successfully'
        ]);
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(ReleaseRepository $releaseRepository, Request $request): Response
    {
        $totalRleases = $releaseRepository->getTotalReleases();

        $page = $request->query->getInt('page', 1);
        $limit = 20;
        $releases = $releaseRepository->paginatedReleases($page, $limit);
        $maxpage = ceil($totalRleases / $limit);

        // Iterate over releases to get artists
        $releasesData = [];

        foreach ($releases as $release) {
            $artists = $release->getArtists();
            $artistsData = [];

            foreach ($artists as $artist) {
                $artistsData[] = [
                    'id' => $artist->getId(),
                    'name' => $artist->getName(),
                ];
            }
            $releasesData[] = [
                'id' => $release->getId(),
                'title' => $release->getTitle(),
                'cover' => $release->getCover(),
                'release_date' => $release->getReleaseDate(),
                'artists' => $artistsData,
            ];
        }

        return $this->json([
            'type' => 'success',
            'releases' => $releasesData,
            'totalReleases' => $totalRleases,
            'maxPage' => $maxpage,
            'page' => $page
        ], 200, [], ['groups' => 'api.release.list']);
    }
}
