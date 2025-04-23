<?php

namespace App\Repository;

use App\Entity\Shelf;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Shelf>
 */
class ShelfRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Shelf::class);
    }

    /**
     * @return Shelf[] Returns an array of Shelf objects
     */
    public function getShelves(): array
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.location', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getTotalShelves(): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id) AS count')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findShelfById($id): ?Shelf
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.id = :val')
            ->setParameter('val', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
