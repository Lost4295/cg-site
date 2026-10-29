<?php

namespace App\Tests;

final class AuthTest extends ApiTestCase
{
    public function testLoginReturnsJwtForValidCredentials(): void
    {
        $this->createUser('login@test.fr', ['ROLE_ADMIN'], [], 'secret-pass');

        $data = $this->jsonLogin(['email' => 'login@test.fr', 'password' => 'secret-pass']);

        $this->assertStatus(200);
        $this->assertArrayHasKey('token', $data);

        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$data['token']);
        $this->request('GET', '/api/points');
        $this->assertStatus(200);
    }

    public function testLoginFailsWithWrongPassword(): void
    {
        $this->createUser('login@test.fr', ['ROLE_ADMIN'], [], 'secret-pass');

        $this->jsonLogin(['email' => 'login@test.fr', 'password' => 'wrong']);

        $this->assertStatus(401);
    }

    public function testLoginFailsForUnknownUser(): void
    {
        $this->jsonLogin(['email' => 'nobody@test.fr', 'password' => 'whatever']);

        $this->assertStatus(401);
    }

    public function testLoginFailsWithMissingFields(): void
    {
        $this->jsonLogin(['email' => 'login@test.fr']);

        $this->assertContains($this->client->getResponse()->getStatusCode(), [400, 401]);
    }

    public function testLoginFailsWithMalformedJson(): void
    {
        $this->client->request('POST', '/auth', [], [], ['CONTENT_TYPE' => 'application/json'], '{bad');

        $this->assertStatus(400);
    }

    public function testApiRejectsInvalidToken(): void
    {
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer not.a.token');

        $this->request('GET', '/api/points');

        $this->assertStatus(401);
    }

    private function jsonLogin(array $body): array
    {
        $this->client->request('POST', '/auth', [], [], ['CONTENT_TYPE' => 'application/json'], json_encode($body));

        return json_decode($this->client->getResponse()->getContent(), true) ?? [];
    }
}
