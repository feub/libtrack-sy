<?php

namespace App\Controller\Api;

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
}
