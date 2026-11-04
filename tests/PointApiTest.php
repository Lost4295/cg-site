<?php

namespace App\Tests;

use App\Entity\GuildInformation;
use App\Entity\Point;
use App\Entity\User;
use PHPUnit\Framework\Attributes\DataProvider;

final class PointApiTest extends ApiTestCase
{
    #[DataProvider('protectedRoutes')]
    public function testRequiresAuthentication(string $method, string $uri): void
    {
        $this->request($method, $uri, 'GET' === $method || 'DELETE' === $method ? null : []);

        $this->assertStatus(401);
    }

    public static function protectedRoutes(): iterable
    {
        yield 'list' => ['GET', '/api/points'];
        yield 'get' => ['GET', '/api/points/1'];
        yield 'create' => ['POST', '/api/points'];
        yield 'replace' => ['PUT', '/api/points/1'];
        yield 'update' => ['PATCH', '/api/points/1'];
        yield 'delete' => ['DELETE', '/api/points/1'];
    }

    public function testNonAdminIsForbidden(): void
    {
        $this->authenticate($this->createUser('plain@test.fr', ['ROLE_USER']));

        $this->request('GET', '/api/points');

        $this->assertStatus(403);
    }

    public function testListReturnsPointsOfAllUserGuilds(): void
    {
        $user = $this->createUser('a@test.fr', ['ROLE_ADMIN'], [self::GUILD_A, self::GUILD_B]);
        $this->createPoint($user, self::GUILD_A);
        $this->createPoint($user, self::GUILD_B);
        $this->createPoint($user, '1000000000000000099');
        $this->authenticate($user);

        $this->request('GET', '/api/points');

        $this->assertStatus(200);
        $this->assertEqualsCanonicalizing(
            [self::GUILD_A, self::GUILD_B],
            array_column($this->decodeCollection(), 'guildId'),
        );
    }

    public function testListCanBeNarrowedToOneOfMyGuilds(): void
    {
        $user = $this->createUser('a@test.fr', ['ROLE_ADMIN'], [self::GUILD_A, self::GUILD_B]);
        $this->createPoint($user, self::GUILD_A);
        $this->createPoint($user, self::GUILD_B);
        $this->authenticate($user);

        $this->request('GET', '/api/points?guild_id='.self::GUILD_B);

        $this->assertStatus(200);
        $this->assertSame([self::GUILD_B], array_column($this->decodeCollection(), 'guildId'));
    }

    public function testListCanBeFilteredByDateRange(): void
    {
        $user = $this->createUser('date-filter@test.fr');
        $before = $this->createPoint($user);
        $before->setDate(new \DateTime('2030-01-15T12:00:00+00:00'));
        $within = $this->createPoint($user);
        $within->setDate(new \DateTime('2030-02-15T12:00:00+00:00'));
        $after = $this->createPoint($user);
        $after->setDate(new \DateTime('2030-03-15T12:00:00+00:00'));
        $this->em->flush();
        $this->authenticate($user);

        $this->request('GET', '/api/points?date[after]=2030-02-01&date[before]=2030-02-28');

        $this->assertStatus(200);
        $this->assertSame([$within->getId()], array_column($this->decodeCollection(), 'id'));
    }

    public function testListCanBeFilteredByUserClass(): void
    {
        $user = $this->createUser('class-filter@test.fr', ['ROLE_ADMIN'], [self::GUILD_A]);
        $targetStudent = $this->createUser('target-class@test.fr', ['ROLE_ADMIN'], [self::GUILD_A]);
        $targetStudent->setClasse('3SI1');
        $otherStudent = $this->createUser('other-class@test.fr', ['ROLE_ADMIN'], [self::GUILD_A]);
        $otherStudent->setClasse('4SI2');
        $targetPoint = $this->createPoint($targetStudent);
        $this->createPoint($otherStudent);
        $this->authenticate($user);

        $this->request('GET', '/api/points?classe=3SI1');

        $this->assertStatus(200);
        $this->assertSame([$targetPoint->getId()], array_column($this->decodeCollection(), 'id'));
    }

    public function testListUsesSlugInsteadOfGuildQueryParameter(): void
    {
        $user = $this->createUser('slug-list@test.fr', ['ROLE_ADMIN'], [self::GUILD_B]);
        $this->em->persist((new GuildInformation())->setGuildId(self::GUILD_B)->setName('Guild B')->setSlug('guild-b'));
        $this->createPoint($user, self::GUILD_A, 20.0);
        $expectedPoint = $this->createPoint($user, self::GUILD_B, 7.0);

        $this->authenticate($user);
        $this->request('GET', '/api/points?guild_id='.self::GUILD_A.'&slug=guild-b');

        $this->assertStatus(200);
        $this->assertSame([$expectedPoint->getId()], array_column($this->decodeCollection(), 'id'));
    }

    public function testPointsByGuildReturnsDetailsAndSignedTotal(): void
    {
        $user = $this->createUser('guild-points@test.fr', ['ROLE_ADMIN'], [self::GUILD_A]);
        $user->setPrenom('Ada');
        $user->setNom('Lovelace');
        $this->em->flush();
        $positivePoint = $this->createPoint($user, self::GUILD_A, 8.0);
        $negativePoint = $this->createPoint($user, self::GUILD_A, -3.0);
        $this->createPoint($user, self::GUILD_B, 100.0);
        $this->authenticate($user);

        $data = $this->request('GET', '/api/points/detailed/'.self::GUILD_A);

        $this->assertStatus(200);
        $this->assertSame(self::GUILD_A, $data['guildId']);
        $this->assertEquals(5.0, $data['total']);
        $this->assertEqualsCanonicalizing(
            [$positivePoint->getId(), $negativePoint->getId()],
            array_column($data['detailed'], 'id'),
        );
        $this->assertSame('Ada Lovelace', $data['detailed'][0]['fullName']);
    }

    public function testDetailedGuildPointsUseSlugInsteadOfPathGuildId(): void
    {
        $requestingUser = $this->createUser('slug-detail-requester@test.fr', ['ROLE_ADMIN'], [self::GUILD_B]);
        $this->em->persist((new GuildInformation())->setGuildId(self::GUILD_B)->setName('Guild B')->setSlug('guild-b'));
        $this->createPoint($requestingUser, self::GUILD_A, 20.0);
        $expectedPoint = $this->createPoint($requestingUser, self::GUILD_B, 7.0);
        $this->authenticate($requestingUser);

        $data = $this->request('GET', '/api/points/detailed/'.self::GUILD_A.'?slug=guild-b');

        $this->assertStatus(200);
        $this->assertSame(self::GUILD_B, $data['guildId']);
        $this->assertSame([$expectedPoint->getId()], array_column($data['detailed'], 'id'));
    }

    public function testPointsOfUsersByGuildAggregatesPointsPerUser(): void
    {
        $requestingUser = $this->createUser('guild-users-requester@test.fr', ['ROLE_ADMIN'], [self::GUILD_A]);
        $firstUser = $this->createUser('guild-users-first@test.fr');
        $firstUser->setPrenom('Ada');
        $firstUser->setNom('Lovelace');
        $secondUser = $this->createUser('guild-users-second@test.fr');
        $secondUser->setPrenom('Grace');
        $secondUser->setNom('Hopper');
        $this->em->flush();
        $this->createPoint($firstUser, self::GUILD_A, 8.0);
        $this->createPoint($firstUser, self::GUILD_A, -3.0);
        $this->createPoint($secondUser, self::GUILD_A, -2.0);
        $this->createPoint($firstUser, self::GUILD_B, 100.0);
        $this->authenticate($requestingUser);

        $data = $this->request('GET', '/api/points/users/'.self::GUILD_A);

        $this->assertStatus(200);
        $this->assertSame(self::GUILD_A, $data['guildId']);
        $this->assertEqualsCanonicalizing([
            ['fullName' => 'Ada Lovelace', 'points' => 5.0],
            ['fullName' => 'Grace Hopper', 'points' => -2.0],
        ], $data['users']);
    }

    public function testGuildUserTotalsUseSlugInsteadOfPathGuildId(): void
    {
        $requestingUser = $this->createUser('slug-users-requester@test.fr', ['ROLE_ADMIN'], [self::GUILD_B]);
        $targetUser = $this->createUser('slug-users-target@test.fr');
        $targetUser->setPrenom('Ada');
        $targetUser->setNom('Lovelace');
        $this->em->persist((new GuildInformation())->setGuildId(self::GUILD_B)->setName('Guild B')->setSlug('guild-b'));
        $this->createPoint($targetUser, self::GUILD_A, 20.0);
        $this->createPoint($targetUser, self::GUILD_B, 7.0);
        $this->em->flush();
        $this->authenticate($requestingUser);

        $data = $this->request('GET', '/api/points/users/'.self::GUILD_A.'?slug=guild-b');

        $this->assertStatus(200);
        $this->assertSame(self::GUILD_B, $data['guildId']);
        $this->assertEquals([['fullName' => 'Ada Lovelace', 'points' => 7.0]], $data['users']);
    }

    public function testGuildPointReportsRequireGuildAccess(): void
    {
        $this->authenticate($this->createUser('guild-access@test.fr', ['ROLE_ADMIN'], [self::GUILD_A]));

        $this->request('GET', '/api/points/detailed/'.self::GUILD_B);

        $this->assertStatus(403);
    }

    public function testDetailedGuildPointsCanBeFilteredByClassAndDate(): void
    {
        $requestingUser = $this->createUser('guild-filter-requester@test.fr', ['ROLE_ADMIN'], [self::GUILD_A]);
        $targetUser = $this->createUser('guild-filter-target@test.fr');
        $targetUser->setClasse('3SI1');
        $otherUser = $this->createUser('guild-filter-other@test.fr');
        $otherUser->setClasse('4SI2');
        $included = $this->createPoint($targetUser, self::GUILD_A, 4.0);
        $included->setDate(new \DateTime('2030-02-15T12:00:00+00:00'));
        $outsideRange = $this->createPoint($targetUser, self::GUILD_A, 8.0);
        $outsideRange->setDate(new \DateTime('2030-03-15T12:00:00+00:00'));
        $this->createPoint($otherUser, self::GUILD_A, 100.0);
        $this->em->flush();
        $this->authenticate($requestingUser);

        $data = $this->request(
            'GET',
            '/api/points/detailed/'.self::GUILD_A.'?classe=3SI1&date[after]=2030-02-01&date[before]=2030-02-28',
        );

        $this->assertStatus(200);
        $this->assertSame([$included->getId()], array_column($data['detailed'], 'id'));
    }

    public function testGuildUserTotalsCanBeFilteredByClassAndDate(): void
    {
        $requestingUser = $this->createUser('guild-user-filter-requester@test.fr', ['ROLE_ADMIN'], [self::GUILD_A]);
        $targetUser = $this->createUser('guild-user-filter-target@test.fr');
        $targetUser->setClasse('3SI1');
        $otherUser = $this->createUser('guild-user-filter-other@test.fr');
        $otherUser->setClasse('4SI2');
        $this->createPoint($targetUser, self::GUILD_A, 4.0)->setDate(new \DateTime('2030-02-15T12:00:00+00:00'));
        $this->createPoint($targetUser, self::GUILD_A, 8.0)->setDate(new \DateTime('2030-03-15T12:00:00+00:00'));
        $this->createPoint($otherUser, self::GUILD_A, 100.0)->setDate(new \DateTime('2030-02-15T12:00:00+00:00'));
        $this->em->flush();
        $this->authenticate($requestingUser);

        $data = $this->request(
            'GET',
            '/api/points/users/'.self::GUILD_A.'?classe=3SI1&date[after]=2030-02-01&date[before]=2030-02-28',
        );

        $this->assertStatus(200);
        $this->assertEquals([['fullName' => $targetUser->getFullName(), 'points' => 4.0]], $data['users']);
    }

    public function testListWithForeignGuildIsForbidden(): void
    {
        $user = $this->createUser('a@test.fr');
        $this->authenticate($user);

        $this->request('GET', '/api/points?guild_id='.self::GUILD_B);

        $this->assertStatus(403);
    }

    public function testListIsEmptyForUserWithoutGuild(): void
    {
        $user = $this->createUser('a@test.fr', ['ROLE_ADMIN'], []);
        $this->createPoint($user, self::GUILD_A);
        $this->authenticate($user);

        $this->request('GET', '/api/points');

        $this->assertStatus(200);
        $this->assertSame([], $this->decodeCollection());
    }

    public function testSuperAdminSeesEveryGuild(): void
    {
        $admin = $this->createUser('root@test.fr', ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], []);
        $this->createPoint($admin, self::GUILD_A);
        $this->createPoint($admin, self::GUILD_B);
        $this->authenticate($admin);

        $this->request('GET', '/api/points');
        $this->assertStatus(200);
        $this->assertCount(2, $this->decodeCollection());

        $this->request('GET', '/api/points?guild_id='.self::GUILD_B);
        $this->assertCount(1, $this->decodeCollection());
    }

    public function testGetItemNominalAndForeignGuild(): void
    {
        $user = $this->createUser('a@test.fr');
        $mine = $this->createPoint($user, self::GUILD_A);
        $foreign = $this->createPoint($user, self::GUILD_B);
        $this->authenticate($user);

        $data = $this->request('GET', '/api/points/'.$mine->getId());
        $this->assertStatus(200);
        $this->assertSame(self::GUILD_A, $data['guildId']);

        $this->request('GET', '/api/points/'.$foreign->getId());
        $this->assertStatus(404);
    }

    public function testGetUnknownItemIsNotFound(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $this->request('GET', '/api/points/99999');

        $this->assertStatus(404);
    }

    public function testCreateInMyGuild(): void
    {
        $user = $this->createUser('a@test.fr');
        $this->authenticate($user);

        $data = $this->request('POST', '/api/points', $this->payload($user, ['guildId' => self::GUILD_A, 'points' => 2.5]));

        $this->assertStatus(201);
        $this->assertEquals(2.5, $data['points']);
        $this->assertSame(self::GUILD_A, $data['guildId']);
    }

    public function testCreateInForeignGuildIsForbidden(): void
    {
        $user = $this->createUser('a@test.fr');
        $this->authenticate($user);

        $this->request('POST', '/api/points', $this->payload($user, ['guildId' => self::GUILD_B]));

        $this->assertStatus(403);
        $this->assertSame(0, $this->em->getRepository(Point::class)->count([]));
    }

    public function testSuperAdminCanCreateInAnyGuild(): void
    {
        $root = $this->createUser('root@test.fr', ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], []);
        $this->authenticate($root);

        $data = $this->request('POST', '/api/points', $this->payload($root, ['guildId' => self::GUILD_B]));

        $this->assertStatus(201);
        $this->assertSame(self::GUILD_B, $data['guildId']);
    }

    public function testCreateWithInvalidBodyFails(): void
    {
        $user = $this->createUser('a@test.fr');
        $this->authenticate($user);

        $this->request('POST', '/api/points', $this->payload($user, ['points' => 'not-a-number']));

        $this->assertStatus(422);
    }

    public function testCreateWithTooLongReasonFails(): void
    {
        $user = $this->createUser('a@test.fr');
        $this->authenticate($user);

        $this->request('POST', '/api/points', $this->payload($user, ['reason' => str_repeat('x', 1001)]));

        $this->assertStatus(422);
        $this->assertSame(0, $this->em->getRepository(Point::class)->count([]));
    }

    public function testCreateWithTooLongGuildIdFails(): void
    {
        $root = $this->createUser('root@test.fr', ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], []);
        $this->authenticate($root);

        $this->request('POST', '/api/points', $this->payload($root, ['guildId' => str_repeat('1', 51)]));

        $this->assertStatus(422);
        $this->assertSame(0, $this->em->getRepository(Point::class)->count([]));
    }

    public function testCreateWithoutUserFails(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $this->request('POST', '/api/points', ['points' => 1, 'guildId' => self::GUILD_A]);

        $this->assertStatus(422);
        $this->assertSame(0, $this->em->getRepository(Point::class)->count([]));
    }

    public function testCreateWithMalformedJsonFails(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $this->client->request('POST', '/api/points', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], '{not json');

        $this->assertStatus(400);
    }

    public function testPatchOwnPointAndMoveToForeignGuild(): void
    {
        $user = $this->createUser('a@test.fr');
        $point = $this->createPoint($user, self::GUILD_A, 1.0);
        $this->authenticate($user);

        $data = $this->request('PATCH', '/api/points/'.$point->getId(), ['points' => 9.0]);
        $this->assertStatus(200);
        $this->assertEquals(9.0, $data['points']);

        $this->request('PATCH', '/api/points/'.$point->getId(), ['guildId' => self::GUILD_B]);
        $this->assertStatus(403);
    }

    public function testPatchWithInvalidBodyFails(): void
    {
        $user = $this->createUser('a@test.fr');
        $point = $this->createPoint($user);
        $this->authenticate($user);

        $this->request('PATCH', '/api/points/'.$point->getId(), ['points' => 'abc']);

        $this->assertStatus(422);
    }

    public function testPatchForeignPointIsNotFound(): void
    {
        $user = $this->createUser('a@test.fr');
        $foreign = $this->createPoint($user, self::GUILD_B);
        $this->authenticate($user);

        $this->request('PATCH', '/api/points/'.$foreign->getId(), ['points' => 3]);

        $this->assertStatus(404);
    }

    public function testPutReplacesPoint(): void
    {
        $user = $this->createUser('a@test.fr');
        $point = $this->createPoint($user);
        $this->authenticate($user);

        $data = $this->request('PUT', '/api/points/'.$point->getId(), $this->payload($user, ['points' => 7.0, 'guildId' => self::GUILD_A]));

        $this->assertStatus(200);
        $this->assertEquals(7.0, $data['points']);
    }

    public function testDeleteRequiresSuperAdmin(): void
    {
        $admin = $this->createUser('a@test.fr');
        $point = $this->createPoint($admin);
        $this->authenticate($admin);

        $this->request('DELETE', '/api/points/'.$point->getId());

        $this->assertStatus(403);
    }

    public function testSuperAdminCanDelete(): void
    {
        $root = $this->createUser('root@test.fr', ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], []);
        $point = $this->createPoint($root);
        $id = $point->getId();
        $this->authenticate($root);

        $this->request('DELETE', '/api/points/'.$id);

        $this->assertStatus(204);
        $this->em->clear();
        $this->assertNull($this->em->find(Point::class, $id));
    }

    private function payload(User $user, array $overrides = []): array
    {
        return $overrides + [
            'user' => '/api/users/'.$user->getId(),
            'points' => 1.0,
            'reason' => 'test',
            'guildId' => self::GUILD_A,
        ];
    }

    private function decodeCollection(): array
    {
        $data = json_decode($this->client->getResponse()->getContent(), true);

        return $data['member'] ?? $data['hydra:member'] ?? $data;
    }
}
