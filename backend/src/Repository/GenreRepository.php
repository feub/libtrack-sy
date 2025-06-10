<?php

namespace App\Repository;

use App\Entity\Genre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * @extends ServiceEntityRepository<Genre>
 */
class GenreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Genre::class);
    }

    public function paginatedGenres(?int $page = 1, ?int $limit = 20): PaginationInterface
    {
        $builder = $this->createQueryBuilder('g')
            ->select('g')
            ->orderBy('g.name', 'ASC');

        return $this->paginator->paginate(
            $builder,
            $page,
            $limit,
            [
                'distinct' => false,
                'sortFieldAllowList' => ['g.name']
            ]
        );
    }

    public function getTotalGenres(): int
    {
        return $this->createQueryBuilder('g')
            ->select('COUNT(g.id) AS count')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Genre Returns a Genre object
     */
    public function findGenreByid($id): Genre
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.id = :val')
            ->setParameter('val', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
