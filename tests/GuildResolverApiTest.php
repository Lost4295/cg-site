<?php

namespace App\Tests;

use App\Entity\GuildInformation;

final class GuildResolverApiTest extends ApiTestCase
{
    private function createGuild(string $id, string $name): void
    {
        $g = (new GuildInformation())->setGuildId($id)->setName($name);
        $this->em->persist($g);
        $this->em->flush();
    }

    public function testRequiresAuthentication(): void
    {
        $this->request('GET', '/api/guild_informations');

        $this->assertStatus(401);
    }

    public function testNonAdminIsForbidden(): void
    {
        $this->authenticate($this->createUser('plain@test.fr', ['ROLE_USER']));

        $this->request('GET', '/api/guild_informations');

        $this->assertStatus(403);
    }

    public function testMemberSeesOnlyOwnGuilds(): void
    {
        $this->createGuild(self::GUILD_A, 'Alpha');
        $this->createGuild(self::GUILD_B, 'Beta');
        $this->authenticate($this->createUser('me@test.fr', ['ROLE_ADMIN'], [self::GUILD_A]));

        $data = $this->request('GET', '/api/guild_informations');

        $this->assertStatus(200);
        $this->assertCount(1, $data['member']);
        $this->assertSame('Alpha', $data['member'][0]['name']);

        $this->request('GET', '/api/guild_informations/'.self::GUILD_B);
        $this->assertStatus(404);
        $this->request('GET', '/api/guild_informations/'.self::GUILD_A);
        $this->assertStatus(200);
    }

    public function testSuperAdminSeesAllAndCanCreate(): void
    {
        $this->createGuild(self::GUILD_A, 'Alpha');
        $this->authenticate($this->createUser('root@test.fr', ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']));

        $data = $this->request('GET', '/api/guild_informations');
        $this->assertCount(1, $data['member']);

        $this->request('POST', '/api/guild_informations', ['guildId' => self::GUILD_B, 'name' => 'Beta']);
        $this->assertStatus(201);
    }

    public function testAdminCannotWrite(): void
    {
        $this->authenticate($this->createUser('me@test.fr', ['ROLE_ADMIN'], [self::GUILD_A]));

        $this->request('POST', '/api/guild_informations', ['guildId' => self::GUILD_A, 'name' => 'Alpha']);

        $this->assertStatus(403);
    }

    public function testSummaryShowsGuildNames(): void
    {
        $this->createGuild(self::GUILD_A, 'Alpha');
        $me = $this->createUser('me@test.fr', ['ROLE_ADMIN'], [self::GUILD_A, self::GUILD_B]);
        $this->createPoint($me, self::GUILD_A, 5.0);
        $this->createPoint($me, self::GUILD_B, 2.0);
        $this->authenticate($me);

        $data = $this->request('GET', '/api/points/summary');

        $this->assertSame('Alpha', $data['guilds'][0]['guildName']);
        $this->assertSame(self::GUILD_B, $data['guilds'][1]['guildName']);
    }
}
