<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\SerializerInterface;

class ApiResponseService
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function success(string $message, array $data = [], int $statusCode = Response::HTTP_OK, array $context = []): JsonResponse
    {
        $responseData = [
            'type' => 'success',
            'message' => $message,
            'data' => $data
        ];

        // Serialize the entire response with the provided context
        $serializedData = $this->serializer->serialize($responseData, 'json', $context);

        return new JsonResponse($serializedData, $statusCode, [], true);
    }

    public function error(string $message, int $statusCode = Response::HTTP_BAD_REQUEST, array $data = []): JsonResponse
    {
        $responseData = [
            'type' => 'error',
            'message' => $message
        ];

        if (!empty($data)) {
            $responseData = array_merge($responseData, $data);
        }

        return new JsonResponse($responseData, $statusCode);
    }
}
