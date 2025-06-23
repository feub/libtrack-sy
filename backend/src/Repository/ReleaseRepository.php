<?php

namespace App\Repository;

use App\Entity\Release;
use App\Service\Pagination\PaginationResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;


/**
 * @extends ServiceEntityRepository<Release>
 */
class ReleaseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
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

    public function paginatedReleases(?int $page = 1, ?int $limit = 10, string $sortBy = 'createdAt', string $sortDir = 'DESC', ?string $searchTerm = '', ?string $searchShelf = ''): PaginationResult
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->leftJoin('r.artists', 'a')
            ->leftJoin('r.genres', 'g')
            ->leftJoin('r.shelf', 's')
            ->leftJoin('r.format', 'f')
            ->select('r', 'a', 's', 'g', 'f');

        if (!empty($searchTerm)) {
            $searchTerm = '%' . trim($searchTerm) . '%';
            $queryBuilder->andWhere('a.name LIKE :searchTerm OR r.title LIKE :searchTerm')
                ->setParameter('searchTerm', $searchTerm);
        }

        if (!empty($searchShelf)) {
            $searchShelf = (int) trim($searchShelf);
            $queryBuilder->andWhere('s.id = :searchShelf')
                ->setParameter('searchShelf', $searchShelf);
        }

        // Validate and sanitize sort parameters
        $allowedSortFields = ['title', 'createdAt'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? 'r.' . $sortBy : 'r.title';
        $sortDir = strtoupper($sortDir) === 'DESC' ? 'DESC' : 'ASC';

        $queryBuilder->orderBy($sortBy, $sortDir);

        // Create the main query for pagination
        $query = $queryBuilder
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->getQuery();

        // Use Doctrine Paginator
        $paginator = new Paginator($query, true);
        $totalItems = count($paginator);

        // Get the items as array
        $items = [];
        foreach ($paginator as $item) {
            $items[] = $item;
        }

        return new PaginationResult($items, $page, $limit, $totalItems);
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
