<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\GuildInformation;
use App\Entity\Point;
use App\Entity\User;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class GuildResolverExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private readonly Security $security,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if (GuildInformation::class !== $resourceClass) {
            return;
        }

        $guildId = $this->requestStack->getCurrentRequest()?->query->get('guild_id');
        $guildId = \is_scalar($guildId) ? (string) $guildId : '';
        $isSuperAdmin = $this->security->isGranted('ROLE_SUPER_ADMIN');

        $user = $this->security->getUser();
        $userGuildIds = $user instanceof User ? $user->getGuildIds() : [];
        $alias = $queryBuilder->getRootAliases()[0];

        if ('' !== $guildId) {
            if (!$isSuperAdmin && !\in_array($guildId, $userGuildIds, true)) {
                throw new AccessDeniedHttpException('You do not belong to this guild.');
            }

            $queryBuilder
                ->andWhere(sprintf('%s.guild_id = :guild_id', $alias))
                ->setParameter('guild_id', $guildId);

            return;
        }

        if ($isSuperAdmin) {
            return;
        }

        if ([] === $userGuildIds) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $queryBuilder
            ->andWhere(sprintf('%s.guild_id IN (:user_guild_ids)', $alias))
            ->setParameter('user_guild_ids', $userGuildIds);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if (GuildInformation::class !== $resourceClass || $this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return;
        }

        $user = $this->security->getUser();
        $guildIds = $user instanceof User ? $user->getGuildIds() : [];
        $alias = $queryBuilder->getRootAliases()[0];

        if ([] === $guildIds) {
            $queryBuilder->andWhere('1 = 0');

            return;
        }

        $queryBuilder
            ->andWhere(sprintf('%s.guild_id IN (:user_guild_ids)', $alias))
            ->setParameter('user_guild_ids', $guildIds);
    }
}
