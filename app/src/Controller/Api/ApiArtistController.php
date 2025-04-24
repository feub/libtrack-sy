<?php

namespace App\Controller\Api;

use App\Repository\ArtistRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ApiResponseService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
    public function list(ArtistRepository $artistRepository): Response
    {
        try {
            $total = $artistRepository->getTotalArtists();

            $artists = $artistRepository->getArtists();

            foreach ($artists as $artist) {
                $artistsData[] = [
                    'id' => $artist->getId(),
                    'name' => $artist->getName(),
                    'slug' => $artist->getSlug(),
                    'thumbnail' => $artist->getThumbnail(),
                ];
            }

            return $this->apiResponseService->success(
                'Artists retrieved successfully',
                [
                    'artists' => $artistsData,
                    'totalArtists' => $total
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
}
