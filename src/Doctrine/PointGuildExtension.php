<?php

namespace App\Doctrine;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\GuildInformation;
use App\Entity\Point;
use App\Entity\User;
use App\Repository\GuildResolverRepository;
use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use function is_scalar;
use function is_string;

final readonly class PointGuildExtension implements QueryCollectionExtensionInterface, QueryItemExtensionInterface
{
    public function __construct(
        private Security     $security,
        private RequestStack $requestStack,
        private GuildResolverRepository $guildRepository,
    ) {
    }

    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        ?Operation $operation = null,
        array $context = [],
    ): void {
        if (Point::class !== $resourceClass) {
            return;
        }

        $guildId = $this->requestStack->getCurrentRequest()?->query->get('guild_id');
        $guildId = is_scalar($guildId) ? (string) $guildId : '';
        $classe = $this->requestStack->getCurrentRequest()?->query->get('classe');
        $classe = is_string($classe) ? trim($classe) : '';
        $slug = $this->requestStack->getCurrentRequest()?->query->get('slug');
        if (null !== $slug && !is_string($slug)) {
            throw new BadRequestHttpException('The slug parameter must be a string.');
        }
        $slug = is_string($slug) ? trim($slug) : '';
        $isSuperAdmin = $this->security->isGranted('ROLE_SUPER_ADMIN');

        $user = $this->security->getUser();
        $userGuildIds = $user instanceof User ? $user->getGuildIds() : [];
        $alias = $queryBuilder->getRootAliases()[0];

        if ('' !== $classe) {
            $userAlias = $queryNameGenerator->generateJoinAlias('user');
            $classParameter = $queryNameGenerator->generateParameterName('class');
            $groupParameter = $queryNameGenerator->generateParameterName('group');
            $lastCharacter = substr($classe, -1);
            $hasGroup = ctype_digit($lastCharacter) && '0' !== $lastCharacter;
            $className = $hasGroup ? substr($classe, 0, -1) : $classe;

            $queryBuilder
                ->join(sprintf('%s.user', $alias), $userAlias)
                ->andWhere(sprintf('%s.classe = :%s', $userAlias, $classParameter))
                ->setParameter($classParameter, $className);

            if ($hasGroup) {
                $queryBuilder
                    ->andWhere(sprintf('%s.groupe = :%s', $userAlias, $groupParameter))
                    ->setParameter($groupParameter, (int) $lastCharacter);
            } else {
                $queryBuilder
                    ->andWhere(sprintf('%s.groupe IS NULL', $userAlias));
            }
        }

        if ('' !== $slug) {
            $resolvedGuildId = $this->guildRepository->findGuildIdBySlug($slug);
            if (null === $resolvedGuildId) {
                throw new NotFoundHttpException(sprintf('No guild was found for slug "%s".', $slug));
            }
            if (!$isSuperAdmin && !\in_array($resolvedGuildId, $userGuildIds, true)) {
                throw new AccessDeniedHttpException('You do not belong to this guild.');
            }

            $guildAlias = $queryNameGenerator->generateJoinAlias('guild_information');
            $queryBuilder
                ->leftJoin(
                    GuildInformation::class,
                    $guildAlias,
                    Join::WITH,
                    sprintf('%s.guild_id = %s.guild_id', $guildAlias, $alias),
                )
                ->andWhere(sprintf('%s.slug = :guild_slug', $guildAlias))
                ->andWhere(sprintf('%s.guild_id = :resolved_guild_id', $alias))
                ->setParameter('guild_slug', $slug)
                ->setParameter('resolved_guild_id', $resolvedGuildId);

            return;
        }
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
        if (Point::class !== $resourceClass || $this->security->isGranted('ROLE_SUPER_ADMIN')) {
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
