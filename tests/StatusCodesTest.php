<?php

namespace App\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Tag and Participation: 201/422 and item 200/204 are not testable (composite identifiers cannot be turned into an IRI).
 * One test per documented status code (200/201/204/400/403/404/422/500) of every CRUD route. */
final class StatusCodesTest extends ApiTestCase
{
    private const RESOURCES = ['dates', 'points', 'users'];

    private function superAdmin(): void
    {
        $this->authenticate($this->createUser('root@test.fr', ['ROLE_ADMIN', 'ROLE_SUPER_ADMIN'], []));
    }

    /** @return array<string, string> resource => item URI of an existing entity */
    private function items(): array
    {
        $user = $this->createUser('target@test.fr');

        return [
            'dates' => '/api/dates/'.$this->createDate()->getId(),
            'points' => '/api/points/'.$this->createPoint($user)->getId(),
            'users' => '/api/users/'.$user->getId(),
        ];
    }

    private function rawRequest(string $method, string $uri, string $content): void
    {
        $this->client->request($method, $uri, [], [], [
            'CONTENT_TYPE' => 'PATCH' === $method ? 'application/merge-patch+json' : 'application/ld+json',
            'HTTP_ACCEPT' => 'application/ld+json',
        ], $content);
    }

    private function validBody(string $resource, array $items): array
    {
        return match ($resource) {
            'dates' => ['title' => 'T', 'description' => 'D', 'date' => '2030-01-01T10:00:00+00:00', 'distanciel' => true, 'guildId' => self::GUILD_A],
            'points' => ['user' => $items['users'], 'points' => 2, 'reason' => 'r', 'date' => '2030-01-01T10:00:00+00:00', 'guildId' => self::GUILD_A],
            'users' => ['id' => 'newid', 'pseudo' => 'newpseudo', 'email' => 'new@test.fr', 'nom' => 'N', 'prenom' => 'P', 'classe' => '3SI1', 'avatar' => 'a', 'roles' => [], 'guildIds' => []],
        };
    }

    private function invalidBody(string $resource): array
    {
        return match ($resource) {
            'dates' => ['title' => ''],
            'points' => ['points' => null],
            'users' => ['pseudo' => ''],
        };
    }

    private function failNextRequest(): void
    {
        static::getContainer()->get('event_dispatcher')->addListener(
            KernelEvents::REQUEST,
            static function (RequestEvent $event): void {
                if ($event->isMainRequest() && !$event->getRequest()->attributes->has('exception')) {
                    throw new \RuntimeException('boom');
                }
            },
            -100,
        );
    }

    public static function resources(): iterable
    {
        foreach (self::RESOURCES as $resource) {
            yield $resource => [$resource];
        }
    }

    #[DataProvider('resources')]
    public function testGetCollection200(string $resource): void
    {
        $this->superAdmin();
        $this->request('GET', "/api/$resource");
        $this->assertStatus(200);
    }

    #[DataProvider('resources')]
    public function testGetItem200(string $resource): void
    {
        $this->superAdmin();
        $this->request('GET', $this->items()[$resource]);
        $this->assertStatus(200);
    }

    #[DataProvider('resources')]
    public function testPost201(string $resource): void
    {
        $this->superAdmin();
        $items = $this->items();
        $this->request('POST', "/api/$resource", $this->validBody($resource, $items));
        $this->assertStatus(201);
    }

    #[DataProvider('resources')]
    public function testPut200(string $resource): void
    {
        $this->superAdmin();
        $items = $this->items();
        $body = $this->validBody($resource, $items);
        unset($body['id']);
        $this->request('PUT', $items[$resource], $body);
        $this->assertStatus(200);
    }

    #[DataProvider('resources')]
    public function testPatch200(string $resource): void
    {
        $this->superAdmin();
        $items = $this->items();
        $field = ['dates' => 'title', 'points' => 'reason', 'users' => 'pseudo'][$resource];
        $this->request('PATCH', $items[$resource], [$field => 'modifie']);
        $this->assertStatus(200);
    }

    #[DataProvider('resources')]
    public function testDelete204(string $resource): void
    {
        $this->superAdmin();
        $this->request('DELETE', $this->items()[$resource]);
        $this->assertStatus(204);
    }

    #[DataProvider('resources')]
    public function testDelete403(string $resource): void
    {
        $this->authenticate($this->createUser('admin@test.fr'));
        $this->request('DELETE', $this->items()[$resource]);
        $this->assertStatus(403);
    }

    #[DataProvider('resources')]
    public function testPost400(string $resource): void
    {
        $this->superAdmin();
        $this->rawRequest('POST', "/api/$resource", '{bad');
        $this->assertStatus(400);
    }

    #[DataProvider('resources')]
    public function testPut400(string $resource): void
    {
        $this->superAdmin();
        $this->rawRequest('PUT', $this->items()[$resource], '{bad');
        $this->assertStatus(400);
    }

    #[DataProvider('resources')]
    public function testPatch400(string $resource): void
    {
        $this->superAdmin();
        $this->rawRequest('PATCH', $this->items()[$resource], '{bad');
        $this->assertStatus(400);
    }

    #[DataProvider('resources')]
    public function testPost422(string $resource): void
    {
        $this->superAdmin();
        $items = $this->items();
        $this->request('POST', "/api/$resource", $this->invalidBody($resource) + $this->validBody($resource, $items));
        $this->assertStatus(422);
    }

    #[DataProvider('resources')]
    public function testPut422(string $resource): void
    {
        $this->superAdmin();
        $items = $this->items();
        $body = $this->invalidBody($resource) + $this->validBody($resource, $items);
        unset($body['id']);
        $this->request('PUT', $items[$resource], $body);
        $this->assertStatus(422);
    }

    #[DataProvider('resources')]
    public function testPatch422(string $resource): void
    {
        $this->superAdmin();
        $this->request('PATCH', $this->items()[$resource], $this->invalidBody($resource));
        $this->assertStatus(422);
    }

    public static function itemMethods(): iterable
    {
        foreach (['dates', 'points', 'users', 'tags', 'participations'] as $resource) {
            foreach (['GET', 'PUT', 'PATCH', 'DELETE'] as $method) {
                yield "$method $resource" => [$method, $resource];
            }
        }
    }

    #[DataProvider('itemMethods')]
    public function testItem404(string $method, string $resource): void
    {
        $this->superAdmin();
        $this->request($method, "/api/$resource/unknown-999999", \in_array($method, ['PUT', 'PATCH'], true) ? [] : null);
        $this->assertStatus(404);
    }

    public static function allRoutes(): iterable
    {
        foreach (['dates', 'points', 'users', 'tags', 'participations', 'guild_informations'] as $resource) {
            yield "GET collection $resource" => ['GET', "/api/$resource", null];
            yield "POST $resource" => ['POST', "/api/$resource", []];
        }
        foreach (['dates', 'points', 'users', 'tags', 'participations'] as $resource) {
            yield "GET item $resource" => ['GET', "/api/$resource/1", null];
            yield "PUT $resource" => ['PUT', "/api/$resource/1", []];
            yield "PATCH $resource" => ['PATCH', "/api/$resource/1", []];
            yield "DELETE $resource" => ['DELETE', "/api/$resource/1", null];
        }
        yield 'GET item guild_informations' => ['GET', '/api/guild_informations/1', null];
    }

    #[DataProvider('allRoutes')]
    public function testServerError500(string $method, string $uri, ?array $body): void
    {
        $this->superAdmin();
        $this->failNextRequest();
        $this->request($method, $uri, $body);
        $this->assertStatus(500);
    }

    public function testGuildInformation200And201And404(): void
    {
        $this->superAdmin();

        $this->request('POST', '/api/guild_informations', ['guildId' => self::GUILD_B, 'name' => 'Beta', 'slug' => 'beta']);
        $this->assertStatus(201);

        $this->request('GET', '/api/guild_informations');
        $this->assertStatus(200);

        $this->request('GET', '/api/guild_informations/'.self::GUILD_B);
        $this->assertStatus(200);

        $this->request('GET', '/api/guild_informations/unknown');
        $this->assertStatus(404);
    }

    public function testGuildInformationPost400And422(): void
    {
        $this->superAdmin();

        $this->rawRequest('POST', '/api/guild_informations', '{bad');
        $this->assertStatus(400);

        $this->request('POST', '/api/guild_informations', []);
        $this->assertStatus(422);
    }

    public function testTagAndParticipationCollections200(): void
    {
        $this->superAdmin();
        $this->request('GET', '/api/tags');
        $this->assertStatus(200);
        $this->request('GET', '/api/participations');
        $this->assertStatus(200);
    }

    #[DataProvider('tagAndParticipation')]
    public function testPostMalformed400(string $resource): void
    {
        $this->superAdmin();

        $this->rawRequest('POST', "/api/$resource", '{bad');
        $this->assertStatus(400);
    }

    public static function tagAndParticipation(): iterable
    {
        yield 'tags' => ['tags'];
        yield 'participations' => ['participations'];
    }
}
