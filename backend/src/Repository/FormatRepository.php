<?php

namespace App\Repository;

use App\Entity\Format;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Format>
 */
class FormatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Format::class);
    }

    /**
     * @return Format[] Returns an array of Format objects
     */
    public function getFormats(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.name', 'ASC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function getTotalFormats(): int
    {
        return $this->createQueryBuilder('f')
            ->select('COUNT(f.id) AS count')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findFormatById($id): ?Format
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.id = :val')
            ->setParameter('val', $id)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
}
