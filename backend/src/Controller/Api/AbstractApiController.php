<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use App\Service\ApiResponseService;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractApiController extends AbstractController
{
    protected EntityManagerInterface $entityManager;
    protected ApiResponseService $apiResponseService;

    public function __construct(
        EntityManagerInterface $entityManager,
        ApiResponseService $apiResponseService
    ) {
        $this->entityManager = $entityManager;
        $this->apiResponseService = $apiResponseService;
    }

    /**
     * Find an entity or return a standardized 404 response
     * 
     * @template T
     * @param class-string<T> $entityClass The entity class to find
     * @param mixed $id The entity identifier
     * @param string $repositoryMethod The repository method to use (default: find)
     * @param array $methodParams Additional parameters for the repository method
     * @return T|Response Either the entity or a JSON 404 response
     */
    protected function findOr404(
        string $entityClass,
        mixed $id,
        string $repositoryMethod = 'find',
        array $methodParams = []
    ): mixed {
        $repository = $this->entityManager->getRepository($entityClass);
        $params = array_merge([$id], $methodParams);
        $entity = $repository->$repositoryMethod(...$params);

        if (!$entity) {
            $entityName = (new \ReflectionClass($entityClass))->getShortName();
            return $this->apiResponseService->error(
                $entityName . ' not found',
                Response::HTTP_NOT_FOUND
            );
        }

        return $entity;
    }
}
