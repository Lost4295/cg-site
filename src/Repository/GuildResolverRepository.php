<?php

namespace App\Repository;

use App\Entity\GuildInformation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GuildInformation>
 */
class GuildResolverRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GuildInformation::class);
    }

    /**
     * @param string[] $guildIds
     *
     * @return array<string, string> guild id => name
     */
    public function resolveNames(array $guildIds): array
    {
        if ([] === $guildIds) {
            return [];
        }

        $rows = $this->createQueryBuilder('g')
            ->select('g.guild_id AS id', 'g.name AS name')
            ->where('g.guild_id IN (:ids)')
            ->setParameter('ids', $guildIds)
            ->getQuery()
            ->getArrayResult();

        return array_column($rows, 'name', 'id');
    }

    public function findGuildIdBySlug(string $slug): ?string
    {
        $guildId = $this->createQueryBuilder('g')
            ->select('g.guild_id')
            ->where('g.slug = :slug')
            ->setParameter('slug', $slug)
            ->getQuery()
            ->getOneOrNullResult();

        return null === $guildId ? null : (string) $guildId['guild_id'];
    }

//    /**
//     * @return GuildReseolver[] Returns an array of GuildReseolver objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('g.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?GuildReseolver
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
