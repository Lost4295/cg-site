<?php

namespace App\Tests;

final class PointSummaryTest extends ApiTestCase
{
    public function testRequiresAuthentication(): void
    {
        $this->request('GET', '/api/points/summary');

        $this->assertStatus(401);
    }

    public function testNonAdminIsForbidden(): void
    {
        $this->authenticate($this->createUser('plain@test.fr', ['ROLE_USER']));

        $this->request('GET', '/api/points/summary');

        $this->assertStatus(403);
    }

    public function testSumsMyPointsPerGuild(): void
    {
        $me = $this->createUser('me@test.fr', ['ROLE_ADMIN'], [self::GUILD_A, self::GUILD_B]);
        $other = $this->createUser('other@test.fr', ['ROLE_ADMIN'], [self::GUILD_A]);
        $this->createPoint($me, self::GUILD_A, 5.0);
        $this->createPoint($me, self::GUILD_A, 2.5);
        $this->createPoint($me, self::GUILD_B, 4.0);
        $this->createPoint($me, self::GUILD_B, -1.0);
        $this->createPoint($other, self::GUILD_A, 100.0);
        $this->authenticate($me);

        $data = $this->request('GET', '/api/points/summary');

        $this->assertStatus(200);
        $this->assertEquals([
            ['guildId' => self::GUILD_A, 'guildName' => self::GUILD_A, 'total' => 7.5],
            ['guildId' => self::GUILD_B, 'guildName' => self::GUILD_B, 'total' => 3.0],
        ], $data['guilds']);
        $this->assertEquals(10.5, $data['total']);
    }

    public function testUserWithoutPointsGetsEmptySummary(): void
    {
        $this->authenticate($this->createUser('me@test.fr'));

        $data = $this->request('GET', '/api/points/summary');

        $this->assertStatus(200);
        $this->assertSame([], $data['guilds']);
        $this->assertEquals(0, $data['total']);
    }

    public function testSummaryIsNotShadowedByItemRoute(): void
    {
        $me = $this->createUser('me@test.fr');
        $this->createPoint($me);
        $this->authenticate($me);

        $this->request('GET', '/api/points/summary');

        $this->assertStatus(200);
        $this->request('GET', '/api/points');
        $this->assertStatus(200);
    }
}
