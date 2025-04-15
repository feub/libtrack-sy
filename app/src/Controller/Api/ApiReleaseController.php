<?php

namespace App\Controller\Api;

use App\Entity\Release;
use App\Service\DiscogsService;
use App\Service\ReleaseService;
use App\Repository\ReleaseRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ApiResponseService;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/release', name: 'api.release.')]
final class ApiReleaseController extends AbstractApiController
{
    public function __construct(
        EntityManagerInterface $entityManager,
        ApiResponseService $apiResponseService,
        private HttpClientInterface $client,
        private SluggerInterface $slugger
    ) {
        parent::__construct($entityManager, $apiResponseService);
    }

    #[Route('/health', name: 'health', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->apiResponseService->success('LibTrack API is running.');
    }

    #[Route('/scan', name: 'scan', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function scan(
        Request $request,
        DiscogsService $discogsService
    ): Response {
        $barcode = $request->toArray();
        $barcode = $barcode['barcode'];
        $releases = null;

        if (!$barcode) {
            return $this->apiResponseService->error(
                'Barcode is required',
                Response::HTTP_BAD_REQUEST
            );
        }

        // ApiExceptionSubscriber handles exceptions
        $releases = $discogsService->getReleaseByBarcode($barcode);

        return $this->apiResponseService->success(
            'Available releases for the barcode: ' . $barcode,
            [
                'barcode' => $barcode,
                'releases' => $releases
            ]
        );
    }

    #[Route('/scan/add', name: 'scan.add', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function scanAdd(
        Request $request,
        ReleaseService $releaseService
    ): Response {
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

        // ApiExceptionSubscriber handles exceptions
        $release = $releaseService->addRelease($releaseId, $barcode);
        return $this->apiResponseService->success(
            'Release "' . $release->getTitle() . '" added successfully'
        );
    }

    #[Route('/list', name: 'list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(ReleaseRepository $releaseRepository, Request $request): Response
    {
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
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(int $id, ReleaseService $releaseService): Response
    {
        // Use findOr404 method to find the entity or return a 404
        $releaseOrResponse = $this->findOr404(Release::class, $id);

        // If a response is returned (404), return it
        if ($releaseOrResponse instanceof Response) {
            return $releaseOrResponse;
        }

        // ApiExceptionSubscriber will handle exceptions
        $releaseService->deleteRelease($releaseOrResponse);
        return $this->apiResponseService->success('Release successfully deleted');
    }
}
