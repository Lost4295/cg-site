<?php

namespace App\Tests;

use App\Entity\User;

final class UserApiTest extends ApiTestCase
{
    public function testGetItem(): void
    {
        $admin = $this->createUser('a@test.fr');
        $this->authenticate($admin);

        $data = $this->request('GET', '/api/users/'.$admin->getId());

        $this->assertStatus(200);
        $this->assertSame('a@test.fr', $data['email']);
    }

    public function testCreateNominal(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $this->request('POST', '/api/users', $this->payload());

        $this->assertStatus(201);
        $this->assertNotNull($this->em->getRepository(User::class)->findOneBy(['email' => 'new@test.fr']));
    }

    public function testCreateWithBlankPseudoFails(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $this->request('POST', '/api/users', $this->payload(['pseudo' => '']));

        $this->assertStatus(422);
        $this->assertNull($this->em->getRepository(User::class)->findOneBy(['email' => 'new@test.fr']));
    }

    public function testCreateWithInvalidEmailFails(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $this->request('POST', '/api/users', $this->payload(['email' => 'not-an-email']));

        $this->assertStatus(422);
    }

    public function testCreateWithPlaceholderNameFails(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $this->request('POST', '/api/users', $this->payload(['nom' => 'Nom à définir']));

        $this->assertStatus(422);
    }

    public function testCreateWithMalformedJsonFails(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $this->client->request('POST', '/api/users', [], [], [
            'CONTENT_TYPE' => 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], '{bad');

        $this->assertStatus(400);
    }

    public function testPatchNominal(): void
    {
        $admin = $this->createUser('a@test.fr');
        $this->authenticate($admin);

        $this->request('PATCH', '/api/users/'.$admin->getId(), ['pseudo' => 'renamed']);

        $this->assertStatus(200);
        $this->em->clear();
        $this->assertSame('renamed', $this->em->find(User::class, $admin->getId())->getPseudo());
    }

    public function testPatchWithInvalidEmailFails(): void
    {
        $admin = $this->createUser('a@test.fr');
        $this->authenticate($admin);

        $this->request('PATCH', '/api/users/'.$admin->getId(), ['email' => 'nope']);

        $this->assertStatus(422);
    }

    public function testSuperAdminCanDeleteUser(): void
    {
        $root = $this->createUser('root@test.fr', ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
        $other = $this->createUser('other@test.fr');
        $id = $other->getId();
        $this->authenticate($root);

        $this->request('DELETE', '/api/users/'.$id);

        $this->assertStatus(204);
        $this->em->clear();
        $this->assertNull($this->em->find(User::class, $id));
    }

    private function payload(array $overrides = []): array
    {
        return $overrides + [
            'id' => 'newuser0000000000000000000000001',
            'pseudo' => 'newuser',
            'email' => 'new@test.fr',
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'classe' => '3SI1',
            'dateInscr' => '2025-09-01T00:00:00+00:00',
            'accountValid' => 3,
            'hasGambled' => false,
        ];
    }
}
