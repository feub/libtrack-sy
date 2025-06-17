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

    public function paginatedReleases(?int $page = 1, ?int $limit = 10, ?string $searchTerm = '', ?string $searchShelf = ''): PaginationInterface
    {
        $builder = $this->createQueryBuilder('r')
            ->leftJoin('r.artists', 'a')
            ->leftJoin('r.genres', 'g')
            ->leftJoin('r.shelf', 's')
            ->leftJoin('r.format', 'f')
            ->select('r', 'a', 's', 'g', 'f');

        if (!empty($searchTerm)) {
            $searchTerm = '%' . trim($searchTerm) . '%';
            $builder->andWhere('a.name LIKE :searchTerm OR r.title LIKE :searchTerm')
                ->setParameter('searchTerm', $searchTerm);
        }

        if (!empty($searchShelf)) {
            $searchShelf = (int) trim($searchShelf);
            $builder->andWhere('s.id = :searchShelf')
                ->setParameter('searchShelf', $searchShelf);
        }

        $builder->orderBy('r.createdAt', 'DESC');

        return $this->paginator->paginate(
            $builder,
            $page,
            $limit,
            [
                'distinct' => true,
                'sortFieldAllowList' => ['r.id', 'r.title'],
            ]
        );
    }

    public function getRelease(int $id): ?Release
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.artists', 'a')
            ->leftJoin('r.genres', 'g')
            ->leftJoin('r.shelf', 's')
            ->leftJoin('r.format', 'f')
            ->select('r', 'a', 's', 'f', 'g')
            ->where('r.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getFormatsStats()
    {
        $stats = $this->createQueryBuilder('r')
            ->leftJoin('r.format', 'f')
            ->select('f.id AS format_id', 'f.name AS format_name', 'COUNT(r.id) AS count')
            ->groupBy('f.id')
            ->getQuery()
            ->getResult();

        return $stats;
    }

    public function getGenresStats()
    {
        $stats = $this->createQueryBuilder('r')
            ->leftJoin('r.genres', 'g')
            ->select('g.id AS genre_id', 'g.name AS genre_name', 'COUNT(r.id) AS count')
            ->groupBy('g.id', 'g.name')
            ->orderBy('g.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $stats;
    }
}
