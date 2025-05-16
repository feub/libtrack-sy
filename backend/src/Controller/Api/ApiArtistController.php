<?php

namespace App\Controller\Api;

use App\Dto\ArtistDto;
use App\Entity\Artist;
use App\Service\ArtistService;
use App\Service\ApiResponseService;
use App\Repository\ArtistRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/api/artist', name: 'api.artist.')]
final class ApiArtistController extends AbstractApiController
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
    public function list(Request $request, ArtistService $artistService): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 20);

        $artistsData = $artistService->getPaginatedArtists($page, $limit);

        return $this->apiResponseService->success(
            'Artist retrieved successfully',
            $artistsData,
            200,
            [
                "groups" => ['api.artist.list']
            ]
        );
    }

    #[Route('/', name: 'create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(
        Request $request,
        ArtistService $artistService,
        ValidatorInterface $validator
    ): Response {
        // Example of a POST request to create an artist
        // {
        //     "name": "Nightfall",
        //     "slug": "nightfall",
        //     "thumbnail": "nightfall.jpg"
        //   }

        // Parse request data
        $data = json_decode($request->getContent(), true);

        // Create and validate DTO
        $artistDto = ArtistDto::fromArray($data);
        $violations = $validator->validate($artistDto);

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

        // Create the artist
        $artist = $artistService->createFromDto($artistDto);

        return $this->apiResponseService->success(
            'Artist "' . $artist->getName() . '" created successfully'
        );
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(
        int $id,
        Request $request,
        ArtistService $artistService,
        ValidatorInterface $validator
    ): Response {
        // Example of a PUT request to create an artist
        // {
        //     "name": "Nightfall",
        //     "slug": "nightfall",
        //     "thumbnail": "nightfall.jpg"
        //   }

        $artistOrResponse = $this->findOr404(Artist::class, $id);

        if ($artistOrResponse instanceof Response) {
            return $artistOrResponse;
        }

        // Parse request data
        $data = json_decode($request->getContent(), true);

        // Create and validate DTO
        $artistDto = ArtistDto::fromArray($data);
        $violations = $validator->validate($artistDto);

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

        // Create the artist
        $artist = $artistService->updateFromDto($artistOrResponse, $artistDto);

        return $this->apiResponseService->success(
            'Artist "' . $artist->getName() . '" updated successfully'
        );
    }

    #[Route('/{id}', name: 'view', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function view(int $id, ArtistRepository $artistRepository): Response
    {
        try {
            $artist = $artistRepository->findArtistById($id);

            $artistData = [
                'id' => $artist->getId(),
                'name' => $artist->getName(),
                'slug' => $artist->getSlug(),
                'thumbnail' => $artist->getThumbnail(),
            ];

            return $this->apiResponseService->success(
                'Artist retrieved successfully',
                ['artist' => $artistData]
            );
        } catch (NotFoundHttpException $e) {
            return $this->apiResponseService->error(
                $e->getMessage(),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    #[Route('/{id}', name: 'delete', methods: ['DELETE'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function delete(int $id, ArtistService $artistService): Response
    {
        // Use findOr404 method to find the entity or return a 404
        $artistOrResponse = $this->findOr404(Artist::class, $id);

        // If a response is returned (404), return it
        if ($artistOrResponse instanceof Response) {
            return $artistOrResponse;
        }

        // ApiExceptionSubscriber will handle exceptions
        $artistService->deleteArtist($artistOrResponse);

        return $this->apiResponseService->success('Artist successfully deleted');
    }
}
