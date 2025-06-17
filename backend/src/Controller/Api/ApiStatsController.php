<?php

namespace App\Controller\Api;

use App\Repository\ArtistRepository;
use App\Repository\GenreRepository;
use App\Repository\ReleaseRepository;
use App\Service\ApiResponseService;
use App\Service\ReleaseService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api/stats', name: 'api.stats.')]
final class ApiStatsController extends AbstractApiController
{
    public function __construct(
        EntityManagerInterface $entityManagerInterface,
        ApiResponseService $apiResponseService
    ) {
        parent::__construct($entityManagerInterface, $apiResponseService);
    }

    #[Route('/releases/count', name: 'releases.count', methods: ['GET'])]
    public function getReleasesCountStats(ReleaseRepository $releaseRepository)
    {
        $count = $releaseRepository->getTotalReleases();

        return $this->apiResponseService->success(
            'Releases count',
            ['count' => $count],
            200
        );
    }

    #[Route('/artists/count', name: 'artists.count', methods: ['GET'])]
    public function getArtistsCountStats(ArtistRepository $artistRepository)
    {
        $count = $artistRepository->getTotalArtists();

        return $this->apiResponseService->success(
            'Artists count',
            ['count' => $count],
            200
        );
    }

    #[Route('/genres/count', name: 'genres.count', methods: ['GET'])]
    public function getGenresCountStats(GenreRepository $genreRepository)
    {
        $stats = $genreRepository->getTotalGenres();

        return $this->apiResponseService->success(
            'Genres count',
            ['count' => $stats],
            200
        );
    }

    #[Route('/formats', name: 'formats', methods: ['GET'])]
    public function getFormatsStats(ReleaseRepository $releaseRepository)
    {
        $stats = $releaseRepository->getFormatsStats();

        return $this->apiResponseService->success(
            'Formats stats',
            $stats,
            200
        );
    }

    #[Route('/genres', name: 'genres', methods: ['GET'])]
    public function getGenresStats(ReleaseRepository $releaseRepository)
    {
        $stats = $releaseRepository->getGenresStats();

        return $this->apiResponseService->success(
            'Genres stats',
            $stats,
            200
        );
    }
}
