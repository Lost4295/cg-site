<?php

namespace App\Repository;

use App\Entity\Date;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Date>
 */
class DateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Date::class);
    }

    //    /**
    //     * @return Date[] Returns an array of Date objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('d.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Date
    //    {
    //        return $this->createQueryBuilder('d')
    //            ->andWhere('d.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function findAllFuture(): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.date > :val')
            ->setParameter('val', new \DateTime())
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
    public function getCurrentDate(): ?Date
    {
        $date = new \DateTime('today 14:00:00');
        return $this->createQueryBuilder('d')
            ->andWhere('d.date = :val')
            ->setParameter('val', $date)
            ->orderBy('d.id', 'ASC')
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findDateBetween($start, $end): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.date BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }
}
