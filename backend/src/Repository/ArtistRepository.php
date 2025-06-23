<?php

namespace App\Repository;

use App\Entity\Artist;
use App\Service\Pagination\PaginationResult;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Artist>
 */
class ArtistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Artist::class);
    }

    public function paginatedArtists(int $page = 1, int $limit = 20, string $sortBy = 'name', string $sortDir = 'ASC', ?string $searchArtistName = ''): PaginationResult
    {
        $queryBuilder = $this->createQueryBuilder('a')
            ->select('a');

        if (!empty($searchArtistName)) {
            $queryBuilder->andWhere('a.name LIKE :artistName')
                ->setParameter('artistName', '%' . trim($searchArtistName) . '%');
        }

        // Validate and sanitize sort parameters
        $allowedSortFields = ['name', 'createdAt'];
        $sortBy = in_array($sortBy, $allowedSortFields) ? 'a.' . $sortBy : 'a.name';
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

    /**
     * @return Artist[] Returns an array of Artist objects
     */
    public function getArtists(): array
    {
        return $this->createQueryBuilder('a')
            ->orderBy('a.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getTotalArtists(): int
    {
        return $this->createQueryBuilder('a')
            ->select('COUNT(a.id) AS count')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Artist|null Returns an Artist object or null
     */
    public function findArtistByid($id): ?Artist
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.id = :val')
            ->setParameter('val', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }


    /**
     * @return Artist[] Returns an array of Artist objects
     */
    public function findArtistByname($value): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.name = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            //    ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
}
