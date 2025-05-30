<?php

namespace App\Controller\Api;

use App\Entity\Genre;
use App\Service\GenreService;
use App\Service\ApiResponseService;
use App\Repository\GenreRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
    // #[IsGranted('IS_AUTHENTICATED_FULLY')]
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

    #[Route('/{id}', name: 'view', methods: ['GET'])]
    // #[IsGranted('IS_AUTHENTICATED_FULLY')]
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
    // #[IsGranted('IS_AUTHENTICATED_FULLY')]
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
