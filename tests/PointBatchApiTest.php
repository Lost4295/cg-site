<?php

namespace App\Tests;

use App\Entity\Point;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class PointBatchApiTest extends ApiTestCase
{
    private function json(string $uri, array $body): array
    {
        $this->client->request('POST', $uri, [], [], [
            'HTTP_ACCEPT' => 'application/json',
            'CONTENT_TYPE' => 'application/json',
        ], json_encode($body));

        return json_decode((string) $this->client->getResponse()->getContent(), true) ?? [];
    }

    private function csv(string $content, array $params = []): array
    {
        $path = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($path, $content);
        $file = new UploadedFile($path, 'points.csv', 'text/csv', null, true);

        $this->client->request('POST', '/api/points/batch/csv', $params, ['file' => $file], ['HTTP_ACCEPT' => 'application/json']);

        return json_decode((string) $this->client->getResponse()->getContent(), true) ?? [];
    }

    private function countPoints(): int
    {
        return $this->em->getRepository(Point::class)->count([]);
    }

    public function testRequiresAuthentication(): void
    {
        $this->json('/api/points/batch', []);
        $this->assertStatus(401);
    }

    public function testBatchCreatesPointsInDefaultGuild(): void
    {
        $admin = $this->createUser('admin@test.fr');
        $this->createUser('b@test.fr');
        $this->createUser('c@test.fr');
        $this->authenticate($admin);

        $data = $this->json('/api/points/batch', ['points' => 2, 'reason' => 'GDC', 'members' => ['b@test.fr', 'C@test.fr']]);

        $this->assertStatus(201);
        $this->assertSame(2, $data['created']);
        $this->assertSame(self::GUILD_A, $data['points'][0]['guildId']);
        $this->assertSame(2, $this->countPoints());
    }

    public function testBatchIsAllOrNothing(): void
    {
        $admin = $this->createUser('admin@test.fr');
        $this->createUser('b@test.fr');
        $this->authenticate($admin);

        $data = $this->json('/api/points/batch', ['points' => 1, 'members' => ['b@test.fr', 'ghost@test.fr']]);

        $this->assertStatus(422);
        $this->assertSame('ghost@test.fr', $data['errors'][0]['email']);
        $this->assertSame(0, $this->countPoints());
    }

    public function testBatchRequiresGuildIdWithSeveralGuilds(): void
    {
        $admin = $this->createUser('admin@test.fr', ['ROLE_ADMIN'], [self::GUILD_A, self::GUILD_B]);
        $this->createUser('b@test.fr');
        $this->authenticate($admin);

        $this->json('/api/points/batch', ['points' => 1, 'members' => ['b@test.fr']]);
        $this->assertStatus(400);

        $this->json('/api/points/batch', ['points' => 1, 'members' => ['b@test.fr'], 'guild_id' => self::GUILD_B]);
        $this->assertStatus(201);
    }

    public function testBatchInForeignGuildIsForbiddenButNotForSuperAdmin(): void
    {
        $admin = $this->createUser('admin@test.fr');
        $root = $this->createUser('root@test.fr', ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], []);
        $this->createUser('b@test.fr');
        $body = ['points' => 1, 'members' => ['b@test.fr'], 'guild_id' => self::GUILD_B];

        $this->authenticate($admin);
        $this->json('/api/points/batch', $body);
        $this->assertStatus(403);
        $this->assertSame(0, $this->countPoints());

        $this->authenticate($root);
        $this->json('/api/points/batch', $body);
        $this->assertStatus(201);
    }

    public function testBatchRejectsInvalidBodyAndTooManyMembers(): void
    {
        $this->authenticate($this->createUser('admin@test.fr'));

        $this->json('/api/points/batch', ['points' => 'abc', 'members' => ['b@test.fr']]);
        $this->assertStatus(400);

        $this->json('/api/points/batch', ['points' => 1, 'members' => []]);
        $this->assertStatus(400);

        $this->json('/api/points/batch', ['points' => 1, 'members' => array_fill(0, 501, 'b@test.fr')]);
        $this->assertStatus(400);
    }

    public function testCsvWithCommaAndSemicolon(): void
    {
        $admin = $this->createUser('admin@test.fr');
        $this->createUser('b@test.fr');
        $this->createUser('c@test.fr');
        $this->authenticate($admin);

        $data = $this->csv("mail,point,raison\nb@test.fr,1.5,\"Bravo, super\"\nc@test.fr,-1,\n");
        $this->assertStatus(201);
        $this->assertSame(2, $data['created']);
        $this->assertSame('Bravo, super', $data['points'][0]['reason']);
        $this->assertSame('Aucune raison fournie', $data['points'][1]['reason']);

        $data = $this->csv("mail;point;raison\nb@test.fr;2,5;Merci\n");
        $this->assertStatus(201);
        $this->assertEquals(2.5, $data['points'][0]['points']);
        $this->assertSame(3, $this->countPoints());
    }

    public function testCsvRejectsBadHeaderAndBadRows(): void
    {
        $admin = $this->createUser('admin@test.fr');
        $this->createUser('b@test.fr');
        $this->authenticate($admin);

        $this->csv("mail,point,raison,extra\nb@test.fr,1,x,y\n");
        $this->assertStatus(400);

        $data = $this->csv("mail,point,raison\nb@test.fr,1,ok\nb@test.fr,abc,bad\nghost@test.fr,1,x\n");
        $this->assertStatus(422);
        $this->assertSame([3, 4], array_column($data['errors'], 'line'));
        $this->assertSame(0, $this->countPoints());
    }

    public function testCsvRequiresFileAndGuildAccess(): void
    {
        $admin = $this->createUser('admin@test.fr');
        $this->createUser('b@test.fr');
        $this->authenticate($admin);

        $this->client->request('POST', '/api/points/batch/csv', [], [], ['HTTP_ACCEPT' => 'application/json']);
        $this->assertStatus(400);

        $this->csv("mail,point,raison\nb@test.fr,1,x\n", ['guild_id' => self::GUILD_B]);
        $this->assertStatus(403);
    }
}
