<?php

namespace App\Tests;

use PHPUnit\Framework\Attributes\DataProvider;

/** Authentication / authorization checks shared by every API Platform resource. */
final class ResourceAccessTest extends ApiTestCase
{
    #[DataProvider('routes')]
    public function testAnonymousIsRejected(string $method, string $uri): void
    {
        $this->request($method, $uri, \in_array($method, ['POST', 'PUT', 'PATCH'], true) ? [] : null);

        $this->assertStatus(401);
    }

    #[DataProvider('routes')]
    public function testNonAdminIsForbidden(string $method, string $uri): void
    {
        $this->authenticate($this->createUser('plain@test.fr', ['ROLE_USER']));

        $this->request($method, $uri, \in_array($method, ['POST', 'PUT', 'PATCH'], true) ? [] : null);

        $this->assertStatus(403);
    }

    public static function routes(): iterable
    {
        foreach (['dates', 'participations', 'tags', 'users'] as $resource) {
            yield "$resource list" => ['GET', "/api/$resource"];
            yield "$resource get" => ['GET', "/api/$resource/1"];
            yield "$resource create" => ['POST', "/api/$resource"];
            yield "$resource replace" => ['PUT', "/api/$resource/1"];
            yield "$resource update" => ['PATCH', "/api/$resource/1"];
            yield "$resource delete" => ['DELETE', "/api/$resource/1"];
        }
    }

    #[DataProvider('listRoutes')]
    public function testAdminCanListWithoutData(string $uri): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $this->request('GET', $uri);

        $this->assertStatus(200);
    }

    public static function listRoutes(): iterable
    {
        foreach (['dates', 'participations', 'tags', 'users'] as $resource) {
            yield $resource => ["/api/$resource"];
        }
    }

    #[DataProvider('deleteRoutes')]
    public function testDeleteRequiresSuperAdmin(string $uri): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $this->request('DELETE', $uri);

        $this->assertStatus(403);
    }

    /** Tag and Participation use composite identifiers, whose item routes currently answer 404 ("Invalid uri variables"). */
    public static function deleteRoutes(): iterable
    {
        foreach (['dates', 'users'] as $resource) {
            yield $resource => ["/api/$resource/1"];
        }
    }

    public function testUnknownItemIsNotFound(): void
    {
        $this->authenticate($this->createUser('a@test.fr'));

        $this->request('GET', '/api/dates/99999');

        $this->assertStatus(404);
    }
}
