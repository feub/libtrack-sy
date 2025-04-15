<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\ApiResponseService;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

final class ApiLoginController extends AbstractController
{
    public function __construct(
        private ApiResponseService $apiResponseService,
        private RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private RefreshTokenManagerInterface $refreshTokenManager,
        private JWTEncoderInterface $jwtEncoder,
        private ParameterBagInterface $parameterBag
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

        // Access token
        $token = $jWTTokenManager->create($user);

        // Récupérer la valeur TTL depuis la configuration
        $ttl = $this->parameterBag->get('gesdinet_jwt_refresh_token.ttl');

        // Si la configuration n'est pas disponible, utiliser une valeur par défaut
        if (!$ttl) {
            $ttl = 2592000; // 30 jours par défaut
        }

        // Refresh token avec la TTL de configuration
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl(
            $user,
            $ttl
        );

        // Persistez le refresh token
        $this->refreshTokenManager->save($refreshToken);

        // Décoder le token JWT pour obtenir sa vraie date d'expiration
        $tokenData = $this->jwtEncoder->decode($token);
        $tokenExpiresAt = isset($tokenData['exp']) ? new \DateTime('@' . $tokenData['exp']) : null;

        return $this->json([
            'user'          => $user->getUserIdentifier(),
            'token'         => $token,
            'token_expires_at' => $tokenExpiresAt ? $tokenExpiresAt->format(\DateTimeInterface::ATOM) : null,
            'refresh_token' => $refreshToken->getRefreshToken(),
            'refresh_expires_at' => $refreshToken->getValid()->format(\DateTimeInterface::ATOM)
        ]);
    }

    #[Route('/api/logout', name: 'api.logout', methods: ['POST'])]
    public function logout(Request $request, TokenStorageInterface $tokenStorage, RefreshTokenManagerInterface $refreshTokenManager): JsonResponse
    {
        // Get refresh token if present in the request
        $refreshToken = $request->request->get('refresh_token');

        if ($refreshToken) {
            $refreshTokenEntity = $refreshTokenManager->get($refreshToken);

            if ($refreshTokenEntity) {
                // Delete refresh token
                $refreshTokenManager->delete($refreshTokenEntity);
            }
        }

        // Invalidate JWT token for the current session
        $tokenStorage->setToken(null);

        return $this->apiResponseService->success(
            'Successfully logged out.',
            [],
            Response::HTTP_OK
        );
    }
}
