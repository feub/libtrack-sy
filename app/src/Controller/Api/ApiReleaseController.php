<?php

namespace App\Controller\Api;

use App\Entity\Release;
use App\Service\ApiResponseService;
use App\Service\DiscogsService;
use App\Service\ReleaseService;
use App\Repository\ReleaseRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

#[Route('/api/release', name: 'api.release.')]
final class ApiReleaseController extends AbstractController
{
    public function __construct(
        private HttpClientInterface $client,
        private SluggerInterface $slugger,
        private ApiResponseService $apiResponseService
    ) {}

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->apiResponseService->success('LibTrack API is running.');
    }

    #[Route('/scan', name: 'scan', methods: ['POST'])]
    // #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function scan(
        Request $request,
        DiscogsService $discogsService
    ): Response {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->apiResponseService->error(
                'You need to be logged in to access this resource.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $barcode = $request->toArray();
        $barcode = $barcode['barcode'];
        $releases = null;

        if (!$barcode) {
            return $this->apiResponseService->error(
                'Barcode is required',
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $releases = $discogsService->getReleaseByBarcode($barcode);

            return $this->apiResponseService->success(
                'Available releases for the barcode: ' . $barcode,
                [
                    'barcode' => $barcode,
                    'releases' => $releases
                ]
            );
        } catch (\Exception $e) {
            return $this->apiResponseService->error(
                $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/scan/add', name: 'scan.add', methods: ['POST'])]
    public function scanAdd(
        Request $request,
        ReleaseService $releaseService
    ): Response {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->apiResponseService->error(
                'You need to be logged in to access this resource.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        // Get the JSON payload
        $data = json_decode($request->getContent(), true);
        $releaseId = $data['release_id'] ?? null;
        $barcode = $data['barcode'] ?? null;

        if (!$releaseId) {
            return $this->apiResponseService->error(
                'No release ID provided',
                Response::HTTP_BAD_REQUEST
            );
        }

        if (!$barcode) {
            return $this->apiResponseService->error(
                'No barcode provided',
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $release = $releaseService->addRelease($releaseId, $barcode);
            return $this->apiResponseService->success(
                'Release "' . $release->getTitle() . '" added successfully'
            );
        } catch (BadRequestHttpException $e) {
            return $this->apiResponseService->error(
                $e->getMessage(),
                Response::HTTP_BAD_REQUEST
            );
        } catch (NotFoundHttpException $e) {
            return $this->apiResponseService->error(
                $e->getMessage(),
                Response::HTTP_NOT_FOUND
            );
        } catch (\Exception $e) {
            return $this->apiResponseService->error(
                'An unexpected error occurred: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    public function list(ReleaseRepository $releaseRepository, Request $request): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->apiResponseService->error(
                'You need to be logged in to access this resource.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $totalReleases = $releaseRepository->getTotalReleases();

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $searchTerm = $request->query->getString('search', '');

        $releases = $releaseRepository->paginatedReleases($page, $limit, $searchTerm);

        if (!empty($searchTerm)) {
            $totalReleases = $releases->count();
        }

        $maxpage = ceil($totalReleases / $limit);

        $releasesData = [];

        foreach ($releases as $release) {
            $artists = $release->getArtists();
            $artistsData = [];

            $format = $release->getFormat();
            $shelf = $release->getShelf();

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
                'barcode' => $release->getBarcode(),
                'format' => $format?->getName() ?? '',
                'shelf' => $shelf?->getLocation() ?? ''
            ];
        }

        return $this->apiResponseService->success(
            'Releases retrieved successfully',
            [
                'releases' => $releasesData,
                'totalReleases' => $totalReleases,
                'maxPage' => $maxpage,
                'page' => $page
            ]
        );
    }

    #[Route('/delete/{id}', name: 'delete', methods: ['DELETE'])]
    public function delete(Release $release, ReleaseService $releaseService): Response
    {
        if (!$this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->apiResponseService->error(
                'You need to be logged in to access this resource.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        try {
            $releaseService->deleteRelease($release);
            return $this->apiResponseService->success('Release successfully deleted');
        } catch (NotFoundHttpException $e) {
            return $this->apiResponseService->error(
                $e->getMessage(),
                Response::HTTP_NOT_FOUND
            );
        } catch (\Exception $e) {
            return $this->apiResponseService->error(
                'An unexpected error occurred: ' . $e->getMessage(),
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
