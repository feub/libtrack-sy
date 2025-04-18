<?php

namespace App\Repository;

use App\Entity\Release;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * @extends ServiceEntityRepository<Release>
 */
class ReleaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Release::class);
    }

    public function getTotalReleases(): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id) AS count')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function paginatedReleases(?int $page = 1, ?int $limit = 10, ?string $searchTerm = ''): PaginationInterface
    {
        $builder = $this->createQueryBuilder('r')
            ->leftJoin('r.artists', 'a')
            ->leftJoin('r.shelf', 's')
            ->select('r', 'a', 's');

        if (!empty($searchTerm)) {
            $searchTerm = '%' . trim($searchTerm) . '%';
            $builder->andWhere('a.name LIKE :searchTerm OR r.title LIKE :searchTerm')
                ->setParameter('searchTerm', '%' . trim($searchTerm) . '%');
        }

        $builder->orderBy('r.createdAt', 'DESC');

        return $this->paginator->paginate(
            $builder,
            $page,
            $limit,
            [
                'distinct' => false,
                'sortFieldAllowList' => ['r.id', 'r.title']
            ]
        );
    }

    public function getRelease(int $id): ?Release
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.artists', 'a')
            ->leftJoin('r.shelf', 's')
            ->leftJoin('r.format', 'f')
            ->select('r', 'a', 's', 'f')
            ->where('r.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
