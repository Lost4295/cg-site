<?php
namespace App\Repository;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use App\Entity\Participation;
use Doctrine\ORM\EntityManagerInterface;

/** @implements ProviderInterface<Participation> */
final class ParticipationItemProvider implements ProviderInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function provide(
        Operation $operation,
        array $uriVariables = [],
        array $context = [],
    ): ?Participation {
        return $this->entityManager
            ->getRepository(Participation::class)
            ->findOneBy([
                'user' => $uriVariables['userId'],
                'date' => $uriVariables['dateId'],
            ]);
    }
}
