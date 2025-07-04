<?php

namespace App\Controller\Api;

use App\Entity\User;
use App\Service\MistralAiService;
use App\Service\ReleaseService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/ai', name: 'api.ai.')]
final class ApiAiController extends AbstractApiController
{
    #[Route('/chat', name: 'chat', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function chat(
        Request $request,
        MistralAiService $mistralAiService
    ): Response {
        $data = json_decode($request->getContent(), true);
        $prompt = $data['prompt'] ?? '';

        if (empty($prompt)) {
            return $this->apiResponseService->error(
                'Prompt is required',
                Response::HTTP_BAD_REQUEST
            );
        }

        try {
            $response = $mistralAiService->chatCompletion($prompt/* , $model */);

            return $this->apiResponseService->success(
                'AI response generated successfully',
                [
                    'response' => $response['choices'][0]['message']['content'] ?? '',
                    'usage' => $response['usage'] ?? null
                ]
            );
        } catch (\Exception $e) {
            return $this->apiResponseService->error(
                $e->getMessage(),
                Response::HTTP_SERVICE_UNAVAILABLE
            );
        }
    }

    #[Route('/release/{id}/description', name: 'release.description', methods: ['POST'])]
    #[IsGranted('IS_AUTHENTICATED_FULLY')]
    public function generateReleaseDescription(
        int $id,
        MistralAiService $mistralAiService,
        ReleaseService $releaseService
    ): Response {
        /** @var User $user */
        $user = $this->getUser();

        try {
            $releaseData = $releaseService->getReleaseFormatted($id, $user);
            $description = $mistralAiService->generateReleaseDescription($releaseData);

            return $this->apiResponseService->success(
                'Release description generated successfully',
                ['description' => $description]
            );
        } catch (\Exception $e) {
            return $this->apiResponseService->error(
                $e->getMessage(),
                $e->getCode() ?: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
