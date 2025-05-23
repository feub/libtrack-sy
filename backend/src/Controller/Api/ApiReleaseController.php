<?php

namespace App\Controller\Api;

use App\Entity\Release;
use App\Service\DiscogsService;
use App\Service\ReleaseService;
use App\Dto\ReleaseDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ApiResponseService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

    #[Route('/', name: 'list', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function list(Request $request, ReleaseService $releaseService): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $searchTerm = $request->query->getString('search', '');
        $searchShelf = $request->query->getString('shelf', '');

        $releasesData = $releaseService->getPaginatedReleases($page, $limit, $searchTerm, $searchShelf);

        return $this->apiResponseService->success(
            'Releases retrieved successfully',
            $releasesData,
            200,
            [
                "groups" => ['api.release.list']
            ]
        );
    }

    #[Route('/', name: 'create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(
        Request $request,
        ReleaseService $releaseService,
        ValidatorInterface $validator
    ): Response {
        // Example of a POST request to create a release
        // {
        //     "title": "Nightfall Farmer",
        //     "release_date": 1995,
        //     "barcode": "1234567890",
        //     "artists": [
        // {
        //     "id": 36
        // }],
        //     "cover": "https://yukonwildlife.ca/2021-04-uneasy-neighbours-red-foxes-and-arctic-foxes-in-the-north/",
        //     "format": {
        //       "id": 2
        //     },
        //     "shelf": {
        //       "id": 2
        //     }
        //   }

        // Parse request data
        $data = json_decode($request->getContent(), true);

        // Create and validate DTO
        $releaseDto = ReleaseDto::fromArray($data);
        $violations = $validator->validate($releaseDto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->apiResponseService->error(
                'Validation failed',
                Response::HTTP_BAD_REQUEST,
                ['errors' => $errors]
            );
        }

        // Create the release
        $release = $releaseService->createFromDto($releaseDto);

        return $this->apiResponseService->success(
            'Release "' . $release->getTitle() . '" created successfully'
        );
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(
        int $id,
        Request $request,
        ReleaseService $releaseService,
        ValidatorInterface $validator
    ): Response {
        // Example of a PUT request to edit a release
        // {
        //     "title": "Nightfall Farmer",
        //     "release_date": 1995,
        //     "barcode": "1234567890",
        //     "artists": [
        //          {"id": 37}
        //     ],
        //     "cover": "https://yukonwildlife.ca/2021-04-uneasy-neighbours-red-foxes-and-arctic-foxes-in-the-north/",
        //     "format": {
        //       "id": 2
        //     },
        //     "shelf": {
        //       "id": 2
        //     }
        //   }

        $releaseOrResponse = $this->findOr404(Release::class, $id);

        if ($releaseOrResponse instanceof Response) {
            return $releaseOrResponse;
        }

        // Parse request data
        $data = json_decode($request->getContent(), true);

        // Create and validate DTO
        $releaseDto = ReleaseDto::fromArray($data);

        $violations = $validator->validate($releaseDto);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = $violation->getMessage();
            }

            return $this->apiResponseService->error(
                'Validation failed',
                Response::HTTP_BAD_REQUEST,
                ['errors' => $errors]
            );
        }

        $release = $releaseService->updateFromDto($releaseOrResponse, $releaseDto);

        return $this->apiResponseService->success(
            'Release "' . $release->getTitle() . '" updated successfully'
        );
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
        $shelf = $data['shelf'] ?? null;

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
        $release = $releaseService->addScannedRelease($releaseId, $barcode, $shelf);
        return $this->apiResponseService->success(
            'Release "' . $release->getTitle() . '" added successfully'
        );
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
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

    #[Route('/{id}', name: 'view', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function view(int $id, ReleaseService $releaseService): Response
    {
        try {
            $releaseData = $releaseService->getReleaseFormatted($id);

            return $this->apiResponseService->success(
                'Release details retrieved successfully',
                ['release' => $releaseData]
            );
        } catch (NotFoundHttpException $e) {
            return $this->apiResponseService->error(
                $e->getMessage(),
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
