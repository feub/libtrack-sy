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

    public function paginatedReleases(?int $page = 1, ?int $limit = 10, ?string $searchArtistName = ''): PaginationInterface
    {
        $builder = $this->createQueryBuilder('r')
            ->leftJoin('r.artists', 'a')
            ->select('r', 'a');

        if (!empty($searchArtistName)) {
            $builder->andWhere('a.name LIKE :artistName')
                ->setParameter('artistName', '%' . trim($searchArtistName) . '%');
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

    //    /**
    //     * @return Release[] Returns an array of Release objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('r.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Release
    //    {
    //        return $this->createQueryBuilder('r')
    //            ->andWhere('r.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
