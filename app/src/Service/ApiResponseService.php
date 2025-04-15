<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ApiResponseService
{
    public function success(string $message, array $data = [], int $statusCode = Response::HTTP_OK): JsonResponse
    {
        return new JsonResponse([
            'type' => 'success',
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }

    public function error(string $message, int $statusCode = Response::HTTP_BAD_REQUEST): JsonResponse
    {
        return new JsonResponse([
            'type' => 'error',
            'message' => $message
        ], $statusCode);
    }
}
