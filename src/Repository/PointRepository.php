<?php

namespace App\Repository;

use App\Entity\Date;
use App\Entity\Point;
use App\Entity\Trimestre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query\ResultSetMapping;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Point>
 */
class PointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Point::class);
    }

    //    /**
    //     * @return Point[] Returns an array of Point objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Point
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }

    public function getPointsOfUsers(int $trimestre = 1): array
    {
        /** @var Trimestre[] $dates */
        $dates = $this->getEntityManager()->getRepository(Trimestre::class)->createQueryBuilder('d')
            ->where('d.trimestre = :trimestre'
        )->andWhere('d.niveau = \'3AL\'')->setParameter('trimestre', $trimestre)->getQuery()->getResult();


        $start = $dates[0]->getDateDebut();
        $end = $dates[0]->getDateFin();

        if (!$start || !$end) {
            throw new \Exception("No date");
        }
        $points = $this->createQueryBuilder('p')
            ->innerJoin('p.user', 'u')
            ->andWhere('u.visibility = 1')
            ->andWhere('p.date BETWEEN :start AND :end ')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
             ->getResult();
        $arr = [];

        foreach ($points as $point) {
            if (!isset($arr[$point->getUser()->getId()])) {
                $arr[$point->getUser()->getId()] = $point->getPoints();
            } else {
                $arr[$point->getUser()->getId()] += $point->getPoints();
            }
        }
        arsort($arr);
        return $arr;
    }

    public function getPointsById($id)
    {
        $points = $this->createQueryBuilder('p')
            ->innerJoin('p.user', 'u')
            ->andWhere('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();
        $total = 0;

        foreach ($points as $point) {
            $total += $point->getPoints();
        }
        return $total;
    }
}
