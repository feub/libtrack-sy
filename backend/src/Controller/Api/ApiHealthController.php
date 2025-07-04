<?php

namespace App\Controller\Api;

use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ApiHealthController extends AbstractApiController
{
    #[Route('/api/health', name: 'api.health', methods: ['GET'])]
    public function index(): JsonResponse
    {
        return $this->apiResponseService->success('LibTrack API is running.');
    }

    #[Route('/api/version', name: 'api.version', methods: ['GET'])]
    public function version(): JsonResponse
    {
        $version = $this->getParameter('app.version');

        return $this->apiResponseService->success(
            'LibTrack API version: ' . $version,
            ['version' => $version],
            200
        );
    }
}
