<?php

namespace App\Controller\Api;

use App\Entity\Release;
use App\Repository\FormatRepository;
use App\Service\DiscogsService;
use App\Service\ReleaseService;
use App\Repository\ReleaseRepository;
use App\Repository\ShelfRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ApiResponseService;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[Route('/api/format', name: 'api.format.')]
final class ApiFormatController extends AbstractApiController
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
    public function list(FormatRepository $formatRepository): Response
    {
        try {
            $total = $formatRepository->getTotalFormats();

            $formats = $formatRepository->getFormats();

            foreach ($formats as $format) {
                $formatsData[] = [
                    'id' => $format->getId(),
                    'name' => $format->getName(),
                    'slug' => $format->getSlug(),
                ];
            }

            return $this->apiResponseService->success(
                'Formats retrieved successfully',
                [
                    'formats' => $formatsData,
                    'totalFormats' => $total
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
    public function view(int $id, FormatRepository $formatRepository): Response
    {
        try {
            $format = $formatRepository->findFormatById($id);

            $formatData = [
                'id' => $format->getId(),
                'name' => $format->getName(),
                'slug' => $format->getSlug(),
            ];

            return $this->apiResponseService->success(
                'Format retrieved successfully',
                ['format' => $formatData]
            );
        } catch (NotFoundHttpException $e) {
            return $this->apiResponseService->error(
                $e->getMessage(),
                Response::HTTP_NOT_FOUND
            );
        }
    }
}
