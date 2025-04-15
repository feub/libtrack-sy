<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\ApiResponseService;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

final class ApiLoginController extends AbstractController
{
    public function __construct(
        private ApiResponseService $apiResponseService
    ) {}

    #[Route('/api/login', name: 'api.login', methods: ['POST'])]
    public function index(#[CurrentUser] ?User $user, JWTTokenManagerInterface $jWTTokenManager): JsonResponse
    {
        if (null === $user) {
            return $this->apiResponseService->error(
                'Missing credentials.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $token = $jWTTokenManager->create($user);

        return $this->json([
            'user'  => $user->getUserIdentifier(),
            'token' => $token,
        ]);
    }
}
