<?php

namespace App\Controller\Api;

use App\Repository\ShelfRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ApiResponseService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/api/shelf', name: 'api.shelf.')]
final class ApiShelfController extends AbstractApiController
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
    public function list(ShelfRepository $shelfRepository): Response
    {
        try {
            $total = $shelfRepository->getTotalShelves();

            $shelves = $shelfRepository->getShelves();

            foreach ($shelves as $shelf) {
                $shelvesData[] = [
                    'id' => $shelf->getId(),
                    'location' => $shelf->getLocation(),
                    'slug' => $shelf->getSlug(),
                    'description' => $shelf->getDescription(),
                ];
            }

            return $this->apiResponseService->success(
                'Shelves retrieved successfully',
                [
                    'shelves' => $shelvesData,
                    'totalShelves' => $total
                ]
            );
        } catch (NotFoundHttpException $e) {
            return $this->apiResponseService->error(
                $e->getMessage(),
                Response::HTTP_NOT_FOUND
            );
        }
    }

    #[Route('/{id}', name: 'view', methods: ['GET'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function view(int $id, ShelfRepository $shelfRepository): Response
    {
        try {
            $shelf = $shelfRepository->findShelfById($id);

            $shelfData = [
                'id' => $shelf->getId(),
                'location' => $shelf->getLocation(),
                'slug' => $shelf->getSlug(),
                'description' => $shelf->getDescription(),
            ];

            return $this->apiResponseService->success(
                'Shelf retrieved successfully',
                ['shelf' => $shelfData]
            );
        } catch (NotFoundHttpException $e) {
            return $this->apiResponseService->error(
                $e->getMessage(),
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
