<?php

namespace App\Repository;

use App\Entity\Artist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Knp\Component\Pager\PaginatorInterface;
use Knp\Component\Pager\Pagination\PaginationInterface;

/**
 * @extends ServiceEntityRepository<Artist>
 */
class ArtistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private PaginatorInterface $paginator)
    {
        parent::__construct($registry, Artist::class);
    }

    public function paginatedArtists(?int $page = 1, ?int $limit = 10, ?string $searchArtistName = ''): PaginationInterface
    {
        $builder = $this->createQueryBuilder('a')
            ->select('a');

        if (!empty($searchArtistName)) {
            $builder->andWhere('a.name LIKE :artistName')
                ->setParameter('artistName', '%' . trim($searchArtistName) . '%');
        }

        return $this->paginator->paginate(
            $builder,
            $page,
            $limit,
            [
                'distinct' => false,
                'sortFieldAllowList' => ['a.name']
            ]
        );
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
     * @return Artist Returns an Artist object
     */
    public function findArtistByid($id): Artist
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
