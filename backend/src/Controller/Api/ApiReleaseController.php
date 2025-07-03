<?php

namespace App\Controller\Api;

use App\Entity\Release;
use App\Entity\User;
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
use Symfony\Component\HttpFoundation\File\UploadedFile;

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
        /** @var User $user */
        $user = $this->getUser();

        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);
        $sortBy = $request->query->get('sort', 'createdAt');
        $sortDir = $request->query->get('order', 'DESC');
        $searchTerm = $request->query->getString('search', '');
        $searchShelf = $request->query->getString('shelf', '');
        $featured = $request->query->get('featured', null);

        $releasesData = $releaseService->getPaginatedReleases($user, $page, $limit, $sortBy, $sortDir, $searchTerm, $searchShelf, $featured);

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
        /** @var User $user */
        $user = $this->getUser();

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

        // Create the release for the current user
        $release = $releaseService->createFromDto($releaseDto, $user);

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
        /** @var User $user */
        $user = $this->getUser();

        $release = $this->entityManager->getRepository(Release::class)->getRelease($id, $user);

        if (!$release) {
            return $this->apiResponseService->error(
                'Release not found',
                Response::HTTP_NOT_FOUND
            );
        }

        // Parse request data
        $data = json_decode($request->getContent(), true);

        if ($data['barcode'] === "") {
            $data['barcode'] = null;
        }

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

        $release = $releaseService->updateFromDto($release, $releaseDto, $user);

        return $this->apiResponseService->success(
            'Release "' . $release->getTitle() . '" updated successfully'
        );
    }

    #[Route('/{id}/cover', name: 'cover', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function uploadImage(int $id, Request $request, Release $release, EntityManagerInterface $entityManager): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $release = $this->entityManager->getRepository(Release::class)->getRelease($id, $user);

        if (!$release) {
            return $this->apiResponseService->error(
                'Release not found',
                Response::HTTP_NOT_FOUND
            );
        }

        /** @var UploadedFile $uploadedFile */
        $uploadedFile = $request->files->get('image');

        if (!$uploadedFile) {
            return $this->json(['error' => 'No file uploaded'], Response::HTTP_BAD_REQUEST);
        }

        $release->setCoverFile($uploadedFile);
        $entityManager->persist($release);
        $entityManager->flush();

        return $this->json([
            'success' => true,
            'imageName' => $release->getCover(),
            'imageUrl' => '/images/covers/' . $release->getCover()
        ]);
    }

    #[Route('/set-cover/{id}', name: 'set.cover', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function setCover(
        int $id,
        Request $request,
        ReleaseService $releaseService,
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        $release = $this->entityManager->getRepository(Release::class)->getRelease($id, $user);

        if (!$release) {
            return $this->apiResponseService->error(
                'Release not found',
                Response::HTTP_NOT_FOUND
            );
        }

        // Parse request data
        $data = json_decode($request->getContent(), true);

        if ($data["coverImage"]) {
            $newCover = $releaseService->downloadCovertArt($data["coverImage"], $id);
            $releaseService->setCover($id, $newCover);

            return $this->apiResponseService->success(
                'Release cover image set successfully'
            );
        }

        return $this->apiResponseService->error(
            'No cover image found.',
            Response::HTTP_BAD_REQUEST,
            ['errors' => ['coverImage' => 'Cover image is required']]
        );
    }

    #[Route('/search', name: 'search', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function search(
        Request $request,
        DiscogsService $discogsService
    ): Response {
        $body = $request->toArray();
        $by = $body['by'] ?? "release_title";
        $search = $body['search'] ?? null;
        $limit = $body['limit'] ?? 5;
        $page = $body['page'] ?? 1;

        if (!$search) {
            return $this->apiResponseService->error(
                'Search cannot be empty',
                Response::HTTP_BAD_REQUEST
            );
        }

        // ApiExceptionSubscriber handles exceptions
        $result = $discogsService->searchRelease($by, $search, $limit, $page);

        return $this->apiResponseService->success(
            'Available releases for the : ' . $by . ': ' . $search,
            [
                'by' => $by,
                'search' => $search,
                'releases' => $result['releases'],
                "per_page" => $result['per_page'],
                "page" => $result['page'],
                "pages" => $result['pages'],
                "items" => $result['items'],
            ]
        );
    }

    #[Route('/scan', name: 'scan', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function scan(
        Request $request,
        DiscogsService $discogsService
    ): Response {
        $body = $request->toArray();
        $barcode = $body['barcode'];
        $limit = $body['limit'] ?? 5;
        $page = $body['page'] ?? 1;

        if (!$barcode) {
            return $this->apiResponseService->error(
                'Barcode is required',
                Response::HTTP_BAD_REQUEST
            );
        }

        // ApiExceptionSubscriber handles exceptions
        $result = $discogsService->searchRelease('barcode', $barcode, $limit, $page);

        return $this->apiResponseService->success(
            'Available releases for the barcode: ' . $barcode,
            [
                'barcode' => $barcode,
                'releases' => $result["releases"],
                "per_page" => $result['per_page'],
                "page" => $result['page'],
                "pages" => $result['pages'],
                "items" => $result['items'],
            ]
        );
    }

    #[Route('/scan/add', name: 'scan.add', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function scanAdd(
        Request $request,
        ReleaseService $releaseService
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

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

        // ApiExceptionSubscriber handles exceptions
        $release = $releaseService->addScannedRelease($user, $releaseId, $barcode, $shelf);

        if (!$release) {
            return $this->apiResponseService->error(
                'Failed to add release',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->apiResponseService->success(
            'Release "' . $release->getTitle() . '" added successfully'
        );
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(int $id, ReleaseService $releaseService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $release = $this->entityManager->getRepository(Release::class)->getRelease($id, $user);

        if (!$release) {
            return $this->apiResponseService->error(
                'Release not found',
                Response::HTTP_NOT_FOUND
            );
        }

        // ApiExceptionSubscriber will handle exceptions
        $releaseService->deleteRelease($release);

        return $this->apiResponseService->success('Release successfully deleted');
    }

    #[Route('/{id}', name: 'view', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function view(int $id, ReleaseService $releaseService): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $releaseData = $releaseService->getReleaseFormatted($id, $user);

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
