<?php

namespace App\Controller\Api;

use App\Service\MusicBrainzService;
use App\Service\CoverArtArchiveService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

#[Route('/api/release', name: 'api.release.')]
final class ReleaseController extends AbstractController
{
    #[Route('/', name: 'index', methods: ['GET'])]
    // #[IsGranted('IS_AUTHENTICATED_FULLY', message: 'You need admin privileges to view this page.')]
    public function index(): JsonResponse
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->json([
                'message' => 'You need to be logged in to access this resource.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/Api/ReleaseController.php',
        ]);
    }

    #[Route('/scan', name: 'scan', methods: ['POST'])]
    // #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function scan(
        Request $request,
        MusicBrainzService $musicBrainzService,
        CoverArtArchiveService $coverService
    ): Response {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->json([
                'message' => 'You need to be logged in to access this resource.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $barcode = $request->request->get('barcode');
        $releases = null;

        if (!$barcode) {
            return $this->json(['error' => 'Barcode is required'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            $releaseData = $musicBrainzService->getReleaseByBarcode($barcode);
            $releases = $releaseData["releases"];
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }

        // Attach cover art
        foreach ($releases as $key => $release) {
            $release['cover'] = $coverService->getCoverArtByMbid($release['id']);
            $releases[$key] = $release;
        }

        return $this->json([
            'barcode' => $barcode,
            'releases' => $releases,
            'message' => 'Available releases for the barcode: ' . $barcode,
        ]);
    }
}
