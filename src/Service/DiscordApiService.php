<?php

namespace App\Service;

use App\Model\DiscordUser;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DiscordApiService
{
    public const AUTHORIZATION_BASE_URI = 'https://discord.com/api/oauth2/authorize';

    public const USERS_ME_ENDPOINT = '/api/users/@me';

    public const TOKEN_BASE_URI = 'https://discord.com/api/oauth2/token';

    public function __construct(
        private readonly HttpClientInterface $discordApiClient,
        private readonly SerializerInterface $serializer,
        private readonly string              $clientId,
        private readonly string              $redirectUri,
        private readonly string              $clientSecret,
    )
    {
    }

    public function getAuthorizationUrl(array $scope, string $state): string
    {
        return self::AUTHORIZATION_BASE_URI . "?" . http_build_query([
                'client_id' => $this->clientId,
                'redirect_uri' => $this->redirectUri,
                'scope' => implode(' ', $scope),
                'response_type' => 'code',
                'state' => $state,
            ]);
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function fetchUser(string $accessToken): DiscordUser
    {
        $response = $this->discordApiClient->request(Request::METHOD_GET, self::USERS_ME_ENDPOINT, [
            'auth_bearer' => $accessToken
        ]);

        $data = $response->getContent();

        return $this->serializer->deserialize($data, DiscordUser::class, 'json');
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     * @throws DecodingExceptionInterface
     */
    public function fetchToken(string $code): string
    {
        $response = $this->discordApiClient->request(Request::METHOD_POST, self::TOKEN_BASE_URI, [
            'headers' => [
                'Content-Type' => 'application/x-www-form-urlencoded',
            ],
            'auth_basic' => [
                $this->clientId,
                $this->clientSecret,
            ],
            'body' => [
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirectUri,
            'code' => $code
        ]]);
        $statusCode = $response->getStatusCode();
        $body = $response->getContent(false);
        dump($statusCode, $body);
        $data = $response->toArray();

        if (!isset($data['access_token']) || !is_string($data['access_token'])) {
            throw new \RuntimeException('Échec de l’authentification Discord.');
        }

        return $data['access_token'];
    }
}
