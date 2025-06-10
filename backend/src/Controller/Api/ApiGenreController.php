<?php

namespace App\Controller\Api;

use App\Dto\GenreDto;
use App\Entity\Genre;
use App\Service\GenreService;
use App\Repository\GenreRepository;
use App\Service\ApiResponseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/api/genre', name: 'api.genre.')]
final class ApiGenreController extends AbstractApiController
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
    public function list(Request $request, GenreService $genreService): Response
    {
        $page = $request->query->getInt('page', 1);
        $limit = $request->query->getInt('limit', 4);

        $genresData = $genreService->getPaginatedGenres($page, $limit);

        return $this->apiResponseService->success(
            'Genres retrieved successfully',
            $genresData,
            200,
            [
                "groups" => ['api.genre.list']
            ]
        );
    }

    #[Route('/', name: 'create', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function create(
        Request $request,
        GenreService $genreService,
        ValidatorInterface $validator
    ): Response {
        // Example of a POST request to create a genre
        // {
        //     "name": "Doom",
        //     "slug": "doom",
        //   }

        // Parse request data
        $data = json_decode($request->getContent(), true);

        // Create and validate DTO
        $genreDto = GenreDto::fromArray($data);
        $violations = $validator->validate($genreDto);

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

        // Create the genre
        $genre = $genreService->createFromDto($genreDto);

        return $this->apiResponseService->success(
            'Genre "' . $genre->getName() . '" created successfully'
        );
    }

    #[Route('/{id}', name: 'edit', methods: ['PUT'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function edit(
        int $id,
        Request $request,
        GenreService $genreService,
        ValidatorInterface $validator
    ): Response {
        // Example of a PUT request to create a genre
        // {
        //     "name": "Doom",
        //     "slug": "doom",
        //   }

        $genreOrResponse = $this->findOr404(Genre::class, $id);

        if ($genreOrResponse instanceof Response) {
            return $genreOrResponse;
        }

        // Parse request data
        $data = json_decode($request->getContent(), true);

        // Create and validate DTO
        $genreDto = GenreDto::fromArray($data);
        $violations = $validator->validate($genreDto);

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

        // Create the genre
        $genre = $genreService->updateFromDto($genreOrResponse, $genreDto);

        return $this->apiResponseService->success(
            'Genre "' . $genre->getName() . '" updated successfully'
        );
    }

    #[Route('/{id}', name: 'view', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function view(int $id, GenreRepository $genreRepository): Response
    {
        try {
            $genre = $genreRepository->findGenreById($id);

            $genreData = [
                'id' => $genre->getId(),
                'name' => $genre->getName(),
                'slug' => $genre->getSlug(),
            ];

            return $this->apiResponseService->success(
                'Genre retrieved successfully',
                ['genre' => $genreData]
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
    public function delete(int $id, GenreService $genreService): Response
    {
        // Use findOr404 method to find the entity or return a 404
        $genreOrResponse = $this->findOr404(Genre::class, $id);

        // If a response is returned (404), return it
        if ($genreOrResponse instanceof Response) {
            return $genreOrResponse;
        }

        // ApiExceptionSubscriber will handle exceptions
        $genreService->deleteGenre($genreOrResponse);

        return $this->apiResponseService->success('Genre successfully deleted');
    }
}
