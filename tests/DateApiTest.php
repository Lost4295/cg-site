<?php

namespace App\Tests;

use App\Entity\Date;

final class DateApiTest extends ApiTestCase
{
    public function testGetItem(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));
        $date = $this->createDate();

        $data = $this->request('GET', '/api/dates/'.$date->getId());

        $this->assertStatus(200);
        $this->assertSame('Soiree', $data['title']);
    }

    public function testCollectionCanBeFilteredByDateRange(): void
    {
        $this->authenticate($this->createUser('date-filter@test.fr'));
        $before = $this->createDate();
        $before->setTitle('Before');
        $before->setDate(new \DateTime('2030-01-15T12:00:00+00:00'));
        $within = $this->createDate();
        $within->setTitle('Within');
        $within->setDate(new \DateTime('2030-02-15T12:00:00+00:00'));
        $after = $this->createDate();
        $after->setTitle('After');
        $after->setDate(new \DateTime('2030-03-15T12:00:00+00:00'));
        $this->em->flush();

        $this->request('GET', '/api/dates?date[after]=2030-02-01&date[before]=2030-02-28');

        $this->assertStatus(200);
        $data = json_decode($this->client->getResponse()->getContent(), true);
        $members = $data['member'] ?? $data['hydra:member'] ?? [];
        $this->assertSame(['Within'], array_column($members, 'title'));
    }

    public function testCreateNominal(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $data = $this->request('POST', '/api/dates', $this->payload());

        $this->assertStatus(201);
        $this->assertSame('New date', $data['title']);
        $this->assertSame(1, $this->em->getRepository(Date::class)->count([]));
    }

    public function testCreateWithInvalidTypesFails(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $this->request('POST', '/api/dates', $this->payload(['distanciel' => 'maybe', 'date' => 'not-a-date']));

                $this->assertStatus(422);
        $this->assertSame(0, $this->em->getRepository(Date::class)->count([]));
    }

    public function testCreateWithMalformedJsonFails(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $this->client->request('POST', '/api/dates', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], '{bad');

        $this->assertStatus(400);
    }

    public function testCreateWithBlankTitleFails(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $this->request('POST', '/api/dates', $this->payload(['title' => '']));

        $this->assertStatus(422);
        $this->assertSame(0, $this->em->getRepository(Date::class)->count([]));
    }

    public function testCreateWithoutGuildIdFails(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));
        $body = $this->payload();
        unset($body['guildId']);

        $this->request('POST', '/api/dates', $body);

        $this->assertContains($this->client->getResponse()->getStatusCode(), [422, 500]);
        $this->assertSame(0, $this->em->getRepository(Date::class)->count([]));
    }

    public function testCreateWithTooLongGuildIdFails(): void
    {
        $this->authenticate($this->createUser('a@test.fr', ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']));

        $this->request('POST', '/api/dates', $this->payload(['guildId' => str_repeat('1', 51)]));

        $this->assertStatus(422);
        $this->assertSame(0, $this->em->getRepository(Date::class)->count([]));
    }

    public function testPatchNominal(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));
        $date = $this->createDate();

        $data = $this->request('PATCH', '/api/dates/'.$date->getId(), ['title' => 'Renamed']);

        $this->assertStatus(200);
        $this->assertSame('Renamed', $data['title']);
    }

    public function testPatchWithInvalidTypeFails(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));
        $date = $this->createDate();

        $this->request('PATCH', '/api/dates/'.$date->getId(), ['distanciel' => 'maybe']);

        $this->assertStatus(422);
    }

    public function testPatchUnknownDateIsNotFound(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $this->request('PATCH', '/api/dates/99999', ['title' => 'x']);

        $this->assertStatus(404);
    }

    public function testSuperAdminCanDelete(): void
    {
        $this->authenticate($this->createUser('root@test.fr', ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']));
        $date = $this->createDate();
        $id = $date->getId();

        $this->request('DELETE', '/api/dates/'.$id);

        $this->assertStatus(204);
        $this->em->clear();
        $this->assertNull($this->em->find(Date::class, $id));
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'title' => 'New date',
            'description' => 'Some description',
            'date' => '2030-05-01T10:00:00+00:00',
            'distanciel' => true,
            'guildId' => self::GUILD_A,
        ];
    }
}
