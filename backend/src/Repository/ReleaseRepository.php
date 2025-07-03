<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\Release;
use Doctrine\Persistence\ManagerRegistry;
use App\Service\Pagination\PaginationResult;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;


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

    public function paginatedReleases(User $user, ?int $page = 1, ?int $limit = 10, string $sortBy = 'createdAt', string $sortDir = 'DESC', ?string $searchTerm = '', ?string $searchShelf = '', ?bool $featured = null): PaginationResult
    {
        $queryBuilder = $this->createQueryBuilder('r')
            ->leftJoin('r.artists', 'a')
            ->leftJoin('r.genres', 'g')
            ->leftJoin('r.shelf', 's')
            ->leftJoin('r.format', 'f')
            ->leftJoin('r.users', 'u')
            ->where('u.id = :userId')
            ->setParameter('userId', $user->getId())
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

        if ($featured !== null) {
            $queryBuilder->andWhere('r.featured = :featured')
                ->setParameter('featured', $featured);
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

    public function getRelease(int $id, User $user): ?Release
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.artists', 'a')
            ->leftJoin('r.genres', 'g')
            ->leftJoin('r.shelf', 's')
            ->leftJoin('r.format', 'f')
            ->leftJoin('r.users', 'u')
            ->where('r.id = :id')
            ->andWhere('u.id = :userId')
            ->setParameter('id', $id)
            ->setParameter('userId', $user->getId())
            ->select('r', 'a', 's', 'f', 'g')
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

    /**
     * Find a release by barcode for a specific user
     *
     * @param string $barcode
     * @param User $user
     * @return Release|null
     */
    public function findByBarcodeAndUser(string $barcode, User $user): ?Release
    {
        return $this->createQueryBuilder('r')
            ->leftJoin('r.users', 'u')
            ->where('r.barcode = :barcode')
            ->andWhere('u.id = :userId')
            ->setParameter('barcode', $barcode)
            ->setParameter('userId', $user->getId())
            ->getQuery()
            ->getOneOrNullResult();
    }
}
