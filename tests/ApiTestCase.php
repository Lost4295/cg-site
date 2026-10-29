<?php

namespace App\Tests;

use App\Entity\Date;
use App\Entity\Point;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class ApiTestCase extends WebTestCase
{
    protected const GUILD_A = '1000000000000000001';
    protected const GUILD_B = '1000000000000000002';

    protected KernelBrowser $client;
    protected EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);

        $schemaTool = new SchemaTool($this->em);
        $metadata = $this->em->getMetadataFactory()->getAllMetadata();
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function createUser(string $email, array $roles = ['ROLE_ADMIN'], array $guildIds = [self::GUILD_A], ?string $plainPassword = null): User
    {
        $user = (new User())
            ->setId(substr(md5($email), 0, 32))
            ->setEmail($email)
            ->setPseudo(strstr($email, '@', true))
            ->setNom('Nom')
            ->setPrenom('Prenom')
            ->setClasse('3SI1')
            ->setRoles($roles)
            ->setGuildIds($guildIds)
            ->setDateInscr(new \DateTime())
            ->setAvatar('avatar');
        $user->setAccountValid(User::IS_OK);

        if (null !== $plainPassword) {
            $user->setPassword(password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 4]));
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    protected function createPoint(User $user, string $guildId = self::GUILD_A, float $points = 5.0): Point
    {
        $point = (new Point())
            ->setUser($user)
            ->setGuildId($guildId)
            ->setPoints($points)
            ->setReason('test');
        $this->em->persist($point);
        $this->em->flush();

        return $point;
    }

    protected function createDate(string $guildId = self::GUILD_A): Date
    {
        $date = (new Date())
            ->setTitle('Soiree')
            ->setDescription('Description')
            ->setDate(new \DateTime('2030-01-01'))
            ->setDistanciel(false);
        $date->setGuildId($guildId);
        $this->em->persist($date);
        $this->em->flush();

        return $date;
    }

    protected function authenticate(User $user): void
    {
        $token = static::getContainer()->get(JWTTokenManagerInterface::class)->create($user);
        $this->client->setServerParameter('HTTP_AUTHORIZATION', 'Bearer '.$token);
    }

    protected function request(string $method, string $uri, ?array $body = null): array
    {
        $headers = ['HTTP_ACCEPT' => 'application/ld+json'];
        $content = null;

        if (null !== $body) {
            $headers['CONTENT_TYPE'] = 'PATCH' === $method ? 'application/merge-patch+json' : 'application/ld+json';
            $content = json_encode($body);
        }

        $this->client->request($method, $uri, [], [], $headers, $content);
        $raw = $this->client->getResponse()->getContent();

        return $raw ? (json_decode($raw, true) ?? []) : [];
    }

    protected function assertStatus(int $expected): void
    {
        $this->assertSame(
            $expected,
            $this->client->getResponse()->getStatusCode(),
            (string) $this->client->getResponse()->getContent(),
        );
    }
}
