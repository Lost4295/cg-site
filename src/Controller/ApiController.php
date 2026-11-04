<?php

namespace App\Controller;

use App\Entity\Point;
use App\Entity\User;
use App\Repository\GuildResolverRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;


final class ApiController extends AbstractController
{
    public function pointsSummary(EntityManagerInterface $em, GuildResolverRepository $resolver): JsonResponse
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            return new JsonResponse(['message' => 'Unauthorized'], 401);
        }

        $rows = $em->createQueryBuilder()
            ->select('p.guild_id AS guildId', 'SUM(p.points) AS total')
            ->from(Point::class, 'p')
            ->where('p.user = :user')
            ->setParameter('user', $user)
            ->groupBy('p.guild_id')
            ->orderBy('p.guild_id', 'ASC')
            ->getQuery()
            ->getArrayResult();

        $names = $resolver->resolveNames(array_map(static fn(array $row): string => (string)$row['guildId'], $rows));
        $guilds = array_map(
            static fn(array $row): array => [
                'guildId' => (string)$row['guildId'],
                'guildName' => $names[$row['guildId']] ?? (string)$row['guildId'],
                'total' => (float)$row['total'],
            ],
            $rows,
        );

        return new JsonResponse([
            'guilds' => $guilds,
            'total' => array_sum(array_column($guilds, 'total')),
        ]);
    }

    public function pointsByGuild(
        string $guildId,
        Request $request,
        EntityManagerInterface $em,
        GuildResolverRepository $guildRepository,
    ): JsonResponse
    {
        $guildId = $this->resolveGuildId($guildId, $request, $guildRepository);
        $this->denyAccessUnlessGrantedGuild($guildId);

        $queryBuilder = $em->createQueryBuilder()
            ->select(
                'p.id AS id',
                "CONCAT(u.prenom, ' ', u.nom) AS fullName",
                'p.reason AS reason',
                'p.date AS date',
                'p.points AS points',
            )
            ->from(Point::class, 'p')
            ->join('p.user', 'u')
            ->where('p.guild_id = :guildId')
            ->setParameter('guildId', $guildId);

        $this->applyPointQueryParameters($queryBuilder, $request, 'p');

        $rows = $queryBuilder
            ->orderBy('p.date', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->getQuery()
            ->getArrayResult();

        $detailed = array_map(static fn (array $row): array => [
            'id' => (int) $row['id'],
            'fullName' => (string) $row['fullName'],
            'reason' => $row['reason'],
            'date' => $row['date'] instanceof \DateTimeInterface ? $row['date']->format(\DateTimeInterface::ATOM) : null,
            'points' => (float) $row['points'],
        ], $rows);

        return new JsonResponse([
            'guildId' => $guildId,
            'detailed' => $detailed,
            'total' => array_sum(array_column($detailed, 'points')),
        ]);
    }

    public function pointsOfUsersByGuild(
        string $guildId,
        Request $request,
        EntityManagerInterface $em,
        GuildResolverRepository $guildRepository,
    ): JsonResponse
    {
        $guildId = $this->resolveGuildId($guildId, $request, $guildRepository);
        $this->denyAccessUnlessGrantedGuild($guildId);

        $queryBuilder = $em->createQueryBuilder()
            ->select("CONCAT(u.prenom, ' ', u.nom) AS fullName", 'SUM(p.points) AS points')
            ->from(Point::class, 'p')
            ->join('p.user', 'u')
            ->where('p.guild_id = :guildId')
            ->setParameter('guildId', $guildId);

        $this->applyPointQueryParameters($queryBuilder, $request, 'p');

        $rows = $queryBuilder
            ->groupBy('u.id', 'u.prenom', 'u.nom')
            ->orderBy('u.nom', 'ASC')
            ->addOrderBy('u.prenom', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return new JsonResponse([
            'guildId' => $guildId,
            'users' => array_map(static fn (array $row): array => [
                'fullName' => (string) $row['fullName'],
                'points' => (float) $row['points'],
            ], $rows),
        ]);
    }

    private function resolveGuildId(string $guildId, Request $request, GuildResolverRepository $guildRepository): string
    {
        $slug = $request->query->all()['slug'] ?? null;
        if (null === $slug) {
            return $guildId;
        }

        if (!is_string($slug) || '' === ($slug = trim($slug))) {
            throw new BadRequestHttpException('The slug parameter must be a non-empty string.');
        }

        $resolvedGuildId = $guildRepository->findGuildIdBySlug($slug);
        if (null === $resolvedGuildId) {
            throw $this->createNotFoundException(sprintf('No guild was found for slug "%s".', $slug));
        }

        return $resolvedGuildId;
    }

    private function applyPointQueryParameters(QueryBuilder $queryBuilder, Request $request, string $pointAlias): void
    {
        $query = $request->query->all();
        $class = $query['classe'] ?? null;
        if (null !== $class && !is_string($class)) {
            throw new BadRequestHttpException('The classe parameter must be a string.');
        }

        if (is_string($class) && '' !== ($class = trim($class))) {
            $lastCharacter = substr($class, -1);
            $hasGroup = ctype_digit($lastCharacter) && '0' !== $lastCharacter;
            $className = $hasGroup ? substr($class, 0, -1) : $class;

            $queryBuilder
                ->join(sprintf('%s.user', $pointAlias), 'filterUser')
                ->andWhere('filterUser.classe = :filterClass')
                ->setParameter('filterClass', $className);

            if ($hasGroup) {
                $queryBuilder
                    ->andWhere('filterUser.groupe = :filterGroup')
                    ->setParameter('filterGroup', (int) $lastCharacter);
            } else {
                $queryBuilder->andWhere('filterUser.groupe IS NULL');
            }
        }

        $dateFilters = $query['date'] ?? null;
        if (null !== $dateFilters && !is_array($dateFilters)) {
            throw new BadRequestHttpException('The date parameter must contain date operators.');
        }

        foreach ($dateFilters ?? [] as $operator => $value) {
            $comparison = match ($operator) {
                'after' => '>=',
                'before' => '<=',
                'strictly_after' => '>',
                'strictly_before' => '<',
                default => throw new BadRequestHttpException(sprintf('Unsupported date operator "%s".', $operator)),
            };

            if (!is_string($value) || '' === trim($value)) {
                throw new BadRequestHttpException(sprintf('The date[%s] parameter must be a date string.', $operator));
            }

            try {
                $date = new \DateTimeImmutable($value);
            } catch (\Exception $exception) {
                throw new BadRequestHttpException(sprintf('The date[%s] parameter is not a valid date.', $operator), $exception);
            }

            $parameter = 'filterDate_'.$operator;
            $queryBuilder
                ->andWhere(sprintf('%s.date %s :%s', $pointAlias, $comparison, $parameter))
                ->setParameter($parameter, $date);
        }
    }

    private function denyAccessUnlessGrantedGuild(string $guildId): void
    {
        $user = $this->getUser();
        if (!$this->isGranted('ROLE_SUPER_ADMIN') && (!$user instanceof User || !$user->hasGuild($guildId))) {
            throw $this->createAccessDeniedException('You do not belong to this guild.');
        }
    }


    #[Route('/classes', name: 'classes')]
    public function classes(): JsonResponse
    {
        return new JsonResponse([
            ["label" => '1A1', "description" => 'Première année Alternance Groupe 1', "value" => '1A1', "emoji" => '1️⃣'],
            ["label" => '1A2', "description" => 'Première année Alternance Groupe 2', "value" => '1A2', "emoji" => '1️⃣'],
            ["label" => '1A3', "description" => 'Première année Alternance Groupe 3', "value" => '1A3', "emoji" => '1️⃣'],
            ["label" => '1A4', "description" => 'Première année Alternance Groupe 4', "value" => '1A4', "emoji" => '1️⃣'],
            ["label" => '1A5', "description" => 'Première année Alternance Groupe 5', "value" => '1A5', "emoji" => '1️⃣'],
            ["label" => '1A6', "description" => 'Première année Alternance Groupe 6', "value" => '1A6', "emoji" => '1️⃣'],
            ["label" => '1A7', "description" => 'Première année Alternance Groupe 7', "value" => '1A7', "emoji" => '1️⃣'],
            ["label" => '1I1', "description" => 'Première année Initial Groupe 1', "value" => '1I1', "emoji" => '1️⃣'],
            ["label" => '1I2', "description" => 'Première année Initial Groupe 2', "value" => '1I2', "emoji" => '1️⃣'],
            ["label" => '2A1', "description" => 'Deuxième année Alternance Groupe 1', "value" => '2A1', "emoji" => '2️⃣'],
            ["label" => '2A2', "description" => 'Deuxième année Alternance Groupe 2', "value" => '2A2', "emoji" => '2️⃣'],
            ["label" => '2A3', "description" => 'Deuxième année Alternance Groupe 3', "value" => '2A3', "emoji" => '2️⃣'],
            ["label" => '2A4', "description" => 'Deuxième année Alternance Groupe 4', "value" => '2A4', "emoji" => '2️⃣'],
            ["label" => '2A5', "description" => 'Deuxième année Alternance Groupe 5', "value" => '2A5', "emoji" => '2️⃣'],
            ["label" => '2I1', "description" => 'Deuxième année Initial Groupe 1', "value" => '2I1', "emoji" => '2️⃣'],
            ["label" => '2I2', "description" => 'Deuxième année Initial Groupe 2', "value" => '2I2', "emoji" => '2️⃣'],
            ["label" => '2MCSI', "description" => 'Deuxième année MCSI', "value" => '2MCSI', "emoji" => '2️⃣'],
            ["label" => '3AL1', "description" => 'Troisième année AL Groupe 1', "value" => '3AL1', "emoji" => '3️⃣'],
            ["label" => '3AL2', "description" => 'Troisième année AL Groupe 2', "value" => '3AL2', "emoji" => '3️⃣'],
            ["label" => '3IABD1', "description" => 'Troisième année IABD Groupe 1', "value" => '3IABD1', "emoji" => '3️⃣'],
            ["label" => '3IABD2', "description" => 'Troisième année IABD Groupe 2', "value" => '3IABD2', "emoji" => '3️⃣'],
            ["label" => '3MOC', "description" => 'Troisième année MOC', "value" => '3MOC', "emoji" => '3️⃣'],
            ["label" => '3IBC', "description" => 'Troisième année IBC', "value" => '3IBC', "emoji" => '3️⃣'],
            ["label" => '3IW1', "description" => 'Troisième année IW Groupe 1', "value" => '3IW1', "emoji" => '3️⃣'],
            ["label" => '3IW2', "description" => 'Troisième année IW Groupe 2', "value" => '3IW2', "emoji" => '3️⃣'],
            ["label" => '3MCSI1', "description" => 'Troisième année MCSI Groupe 1', "value" => '3MCSI1', "emoji" => '3️⃣'],
            ["label" => '3MCSI2', "description" => 'Troisième année MCSI Groupe 2', "value" => '3MCSI2', "emoji" => '3️⃣'],
            ["label" => '3RVJV', "description" => 'Troisième année RVJV', "value" => '3RVJV', "emoji" => '3️⃣'],
            ["label" => '3SI1', "description" => 'Troisième année SI Groupe 1', "value" => '3SI1', "emoji" => '3️⃣'],
            ["label" => '3SI2', "description" => 'Troisième année SI Groupe 2', "value" => '3SI2', "emoji" => '3️⃣'],
            ["label" => '3SI3', "description" => 'Troisième année SI Groupe 3', "value" => '3SI3', "emoji" => '3️⃣'],
            ["label" => '3SI4', "description" => 'Troisième année SI Groupe 4', "value" => '3SI4', "emoji" => '3️⃣'],
            ["label" => '3SI5', "description" => 'Troisième année SI Groupe 5', "value" => '3SI5', "emoji" => '3️⃣'],
            ["label" => '3SRC1', "description" => 'Troisième année SRC Groupe 1', "value" => '3SRC1', "emoji" => '3️⃣'],
            ["label" => '3SRC2', "description" => 'Troisième année SRC Groupe 2', "value" => '3SRC2', "emoji" => '3️⃣'],
            ["label" => '3SRC3', "description" => 'Troisième année SRC Groupe 3', "value" => '3SRC3', "emoji" => '3️⃣'],
            ["label" => '3SRC4', "description" => 'Troisième année SRC Groupe 4', "value" => '3SRC4', "emoji" => '3️⃣'],
            ["label" => '3SRC5', "description" => 'Troisième année SRC Groupe 5', "value" => '3SRC5', "emoji" => '3️⃣'],
            ["label" => '4AL1', "description" => 'Quatrième année AL Groupe 1', "value" => '4AL1', "emoji" => '4️⃣'],
            ["label" => '4AL2', "description" => 'Quatrième année AL Groupe 2', "value" => '4AL2', "emoji" => '4️⃣'],
            ["label" => '4IABD1', "description" => 'Quatrième année IABD Groupe 1', "value" => '4IABD1', "emoji" => '4️⃣'],
            ["label" => '4IABD2', "description" => 'Quatrième année IABD Groupe 2', "value" => '4IABD2', "emoji" => '4️⃣'],
            ["label" => '4MOC', "description" => 'Quatrième année MOC', "value" => '4MOC', "emoji" => '4️⃣'],
            ["label" => '4IBC', "description" => 'Quatrième année IBC', "value" => '4IBC', "emoji" => '4️⃣'],
            ["label" => '4IW1', "description" => 'Quatrième année IW Groupe 1', "value" => '4IW1', "emoji" => '4️⃣'],
            ["label" => '4IW2', "description" => 'Quatrième année IW Groupe 2', "value" => '4IW2', "emoji" => '4️⃣'],
            ["label" => '4IW3', "description" => 'Quatrième année IW Groupe 3', "value" => '4IW3', "emoji" => '4️⃣'],
            ["label" => '4MCSI1', "description" => 'Quatrième année MCSI Groupe 1', "value" => '4MCSI1', "emoji" => '4️⃣'],
            ["label" => '4MCSI2', "description" => 'Quatrième année MCSI Groupe 2', "value" => '4MCSI2', "emoji" => '4️⃣'],
            ["label" => '4MCSI3', "description" => 'Quatrième année MCSI Groupe 3', "value" => '4MCSI3', "emoji" => '4️⃣'],
            ["label" => '4MCSI4', "description" => 'Quatrième année MCSI Groupe 4', "value" => '4MCSI4', "emoji" => '4️⃣'],
            ["label" => '4RVJV', "description" => 'Quatrième année RVJV', "value" => '4RVJV', "emoji" => '4️⃣'],
            ["label" => '4SI1', "description" => 'Quatrième année SI Groupe 1', "value" => '4SI1', "emoji" => '4️⃣'],
            ["label" => '4SI2', "description" => 'Quatrième année SI Groupe 2', "value" => '4SI2', "emoji" => '4️⃣'],
            ["label" => '4SI3', "description" => 'Quatrième année SI Groupe 3', "value" => '4SI3', "emoji" => '4️⃣'],
            ["label" => '4SI4', "description" => 'Quatrième année SI Groupe 4', "value" => '4SI4', "emoji" => '4️⃣'],
            ["label" => '4SI5', "description" => 'Quatrième année SI Groupe 5', "value" => '4SI5', "emoji" => '4️⃣'],
            ["label" => '4SRC1', "description" => 'Quatrième année SRC Groupe 1', "value" => '4SRC1', "emoji" => '4️⃣'],
            ["label" => '4SRC2', "description" => 'Quatrième année SRC Groupe 2', "value" => '4SRC2', "emoji" => '4️⃣'],
            ["label" => '4SRC3', "description" => 'Quatrième année SRC Groupe 3', "value" => '4SRC3', "emoji" => '4️⃣'],
            ["label" => '4SRC4', "description" => 'Quatrième année SRC Groupe 4', "value" => '4SRC4', "emoji" => '4️⃣'],
            ["label" => '4SRC5', "description" => 'Quatrième année SRC Groupe 5', "value" => '4SRC5', "emoji" => '4️⃣'],
            ["label" => '5MOC', "description" => 'Cinquième année MOC', "value" => '5MOC', "emoji" => '5️⃣'],
            ["label" => '5IBC', "description" => 'Cinquième année IBC', "value" => '5IBC', "emoji" => '5️⃣'],
            ["label" => '5IW1', "description" => 'Cinquième année IW Groupe 1', "value" => '5IW1', "emoji" => '5️⃣'],
            ["label" => '5IW2', "description" => 'Cinquième année IW Groupe 2', "value" => '5IW2', "emoji" => '5️⃣'],
            ["label" => '5AL1', "description" => 'Cinquième année AL Groupe 1', "value" => '5AL1', "emoji" => '5️⃣'],
            ["label" => '5AL2', "description" => 'Cinquième année AL Groupe 2', "value" => '5AL2', "emoji" => '5️⃣'],
            ["label" => '5MCSI1', "description" => 'Cinquième année MCSI Groupe 1', "value" => '5MCSI1', "emoji" => '5️⃣'],
            ["label" => '5MCSI2', "description" => 'Cinquième année MCSI Groupe 2', "value" => '5MCSI2', "emoji" => '5️⃣'],
            ["label" => '5MCSI3', "description" => 'Cinquième année MCSI Groupe 3', "value" => '5MCSI3', "emoji" => '5️⃣'],
            ["label" => '5IABD1', "description" => 'Cinquième année IABD Groupe 1', "value" => '5IABD1', "emoji" => '5️⃣'],
            ["label" => '5IABD2', "description" => 'Cinquième année IABD Groupe 2', "value" => '5IABD2', "emoji" => '5️⃣'],
            ["label" => '5RVJV', "description" => 'Cinquième année RVJV', "value" => '5RVJV', "emoji" => '5️⃣'],
            ["label" => '5SI1', "description" => 'Cinquième année SI Groupe 1', "value" => '5SI1', "emoji" => '5️⃣'],
            ["label" => '5SI2', "description" => 'Cinquième année SI Groupe 2', "value" => '5SI2', "emoji" => '5️⃣'],
            ["label" => '5SI3', "description" => 'Cinquième année SI Groupe 3', "value" => '5SI3', "emoji" => '5️⃣'],
            ["label" => '5SRC1', "description" => 'Cinquième année SRC Groupe 1', "value" => '5SRC1', "emoji" => '5️⃣'],
            ["label" => '5SRC2', "description" => 'Cinquième année SRC Groupe 2', "value" => '5SRC2', "emoji" => '5️⃣'],
            ["label" => '5SRC3', "description" => 'Cinquième année SRC Groupe 3', "value" => '5SRC3', "emoji" => '5️⃣'],
            ["label" => '5SRC4', "description" => 'Cinquième année SRC Groupe 4', "value" => '5SRC4', "emoji" => '5️⃣'],
            ["label" => '1IJ', "description" => 'Première année Initial Janvier', "value" => '1IJ', "emoji" => '1️⃣'],
            ["label" => '1AJ1', "description" => 'Première année Alternance Janvier Groupe 1', "value" => '1AJ1', "emoji" => '1️⃣'],
            ["label" => '1AJ2', "description" => 'Première année Alternance Janvier Groupe 2', "value" => '1AJ2', "emoji" => '1️⃣'],
            ["label" => '2AJ1', "description" => 'Deuxième année Alternance Janvier Groupe 1', "value" => '2AJ1', "emoji" => '2️⃣'],
            ["label" => '2AJ2', "description" => 'Deuxième année Alternance Janvier Groupe 2', "value" => '2AJ2', "emoji" => '2️⃣'],
            ["label" => '3IABDJ', "description" => 'Troisième année IABD Janvier', "value" => '3IABDJ', "emoji" => '3️⃣'],
            ["label" => '3SIJ', "description" => 'Troisième année SI Janvier', "value" => '3SIJ', "emoji" => '3️⃣'],
            ["label" => '3SRCJ', "description" => 'Troisième année SRC Janvier', "value" => '3SRCJ', "emoji" => '3️⃣'],
            ["label" => '3IWJ', "description" => 'Troisième année IW Janvier', "value" => '3IWJ', "emoji" => '3️⃣'],
            ["label" => '3MCSIJ', "description" => 'Troisième année MCSI Janvier', "value" => '3MCSIJ', "emoji" => '3️⃣'],
            ["label" => '4IABDJ', "description" => 'Quatrième année IABD Janvier', "value" => '4IABDJ', "emoji" => '4️⃣'],
            ["label" => '4SIJ', "description" => 'Quatrième année SI Janvier', "value" => '4SIJ', "emoji" => '4️⃣'],
            ["label" => '4SRCJ', "description" => 'Quatrième année SRC Janvier', "value" => '4SRCJ', "emoji" => '4️⃣'],
            ["label" => '4IWJ', "description" => 'Quatrième année IW Janvier', "value" => '4IWJ', "emoji" => '4️⃣'],
            ["label" => '4MCSIJ', "description" => 'Quatrième année MCSI Janvier', "value" => '4MCSIJ', "emoji" => '4️⃣'],
            ["label" => '5IABDJ', "description" => 'Cinquième année IABD Janvier', "value" => '5IABDJ', "emoji" => '5️⃣'],
            ["label" => '5SIJ', "description" => 'Cinquième année SI Janvier', "value" => '5SIJ', "emoji" => '5️⃣'],
            ["label" => '5SRCJ', "description" => 'Cinquième année SRC Janvier', "value" => '5SRCJ', "emoji" => '5️⃣'],
            ["label" => '5IWJ', "description" => 'Cinquième année IW Janvier', "value" => '5IWJ', "emoji" => '5️⃣'],
            ["label" => '5MCSIJ', "description" => 'Cinquième année MCSI Janvier', "value" => '5MCSIJ', "emoji" => '5️⃣'],
        ]);
    }
}
