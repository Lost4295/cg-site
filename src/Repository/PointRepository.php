<?php

namespace App\Repository;

use App\Entity\Date;
use App\Entity\Point;
use App\Entity\Trimestre;
use App\Entity\User;
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

    public function getPointsById($id): array
    {
        $user = $this->getEntityManager()->getRepository(User::class)->createQueryBuilder('u')
            ->select('u.classe')
            ->where('u.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getResult();

        /** @var Trimestre[] $dates */
        $dates = $this->getEntityManager()->getRepository(Trimestre::class)->createQueryBuilder('d')
            ->where('d.niveau = :niveau')->setParameter("niveau", $user[0]['classe'])->getQuery()->getResult();
        $totaux = [];
        foreach ($dates as $date) {

            $start = $date->getDateDebut();
            $end = $date->getDateFin();

            $points = $this->createQueryBuilder('p')
                ->innerJoin('p.user', 'u')
                ->andWhere('p.date BETWEEN :start AND :end ')
                ->andWhere('u.id = :id')
                ->setParameter('id', $id)
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getQuery()
                ->getResult();

            $total = 0;

            foreach ($points as $point) {
                $total += $point->getPoints();
            }
            $totaux[$date->getTrimestre()] = $total;
        }
        return $totaux;
    }

    public function getOpenPointsData(string $keyword): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();

        $qb
            ->select('
        u.nom as nom,
        u.prenom as prenom,
 CASE
        WHEN u.is_admin = 0 AND SUM(CASE WHEN t.trimestre = 1 THEN p.points ELSE 0 END) > 4 THEN 4
        WHEN u.is_admin = 1 AND SUM(CASE WHEN t.trimestre = 1 THEN p.points ELSE 0 END) > 6 THEN 6
        WHEN u.is_admin = 2 AND SUM(CASE WHEN t.trimestre = 1 THEN p.points ELSE 0 END) > 8 THEN 8
        ELSE SUM(CASE WHEN t.trimestre = 1 THEN p.points ELSE 0 END)
    END AS s1,

    CASE
        WHEN u.is_admin = 0 AND SUM(CASE WHEN t.trimestre = 2 THEN p.points ELSE 0 END) > 4 THEN 4
        WHEN u.is_admin = 1 AND SUM(CASE WHEN t.trimestre = 2 THEN p.points ELSE 0 END) > 6 THEN 6
        WHEN u.is_admin = 2 AND SUM(CASE WHEN t.trimestre = 2 THEN p.points ELSE 0 END) > 8 THEN 8
        ELSE SUM(CASE WHEN t.trimestre = 2 THEN p.points ELSE 0 END)
    END AS s2,

    CASE
        WHEN u.is_admin = 0 AND SUM(CASE WHEN t.trimestre = 3 THEN p.points ELSE 0 END) > 4 THEN 4
        WHEN u.is_admin = 1 AND SUM(CASE WHEN t.trimestre = 3 THEN p.points ELSE 0 END) > 6 THEN 6
        WHEN u.is_admin = 2 AND SUM(CASE WHEN t.trimestre = 3 THEN p.points ELSE 0 END) > 8 THEN 8
        ELSE SUM(CASE WHEN t.trimestre = 3 THEN p.points ELSE 0 END)
    END AS s3
    ')
            ->from(Point::class, 'p')
            ->join('p.user', 'u')
            ->join(
                Trimestre::class,
                't',
                'WITH',
                'p.date BETWEEN t.date_debut AND t.date_fin'
            )
            ->where('u.visibility = true')
            ->andWhere('(u.nom LIKE :keyword OR u.prenom LIKE :keyword)')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->groupBy('u.id')
            ->orderBy('u.nom', 'ASC');

        return $qb->getQuery()->getResult();

    }
}
