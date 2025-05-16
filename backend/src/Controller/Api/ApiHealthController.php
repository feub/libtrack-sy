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
}
