<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Auth;

use Alchemyguy\YoutubeLaravelApi\Events\TokenRefreshed;
use Alchemyguy\YoutubeLaravelApi\Exceptions\AuthenticationException;
use Google\Client;
use Illuminate\Support\Facades\Event;

class OAuthService
{
    public function __construct(protected Client $client) {}

    public function client(): Client
    {
        return $this->client;
    }

    public function getLoginUrl(string $youtubeEmail, ?string $channelId = null): string
    {
        if ($channelId !== null && $channelId !== '') {
            $this->client->setState($channelId);
        }
        $this->client->setLoginHint($youtubeEmail);

        return $this->client->createAuthUrl();
    }

    /**
     * @return array<string, mixed>
     */
    public function exchangeCode(string $code): array
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            $msg = (string) ($token['error_description'] ?? $token['error']);
            throw new AuthenticationException("Token exchange failed: {$msg}");
        }

        return $token;
    }

    /**
     * Apply the token to the client; refresh if expired.
     * Returns the refreshed token if a refresh occurred (caller should persist),
     * or null if the existing token was still valid.
     *
     * @param array<string, mixed> $token
     * @return array<string, mixed>|null
     */
    public function setAccessToken(array $token): ?array
    {
        $this->client->setAccessToken($token);

        if (! $this->client->isAccessTokenExpired()) {
            return null;
        }

        $refreshToken = $token['refresh_token'] ?? $this->client->getRefreshToken();
        if (empty($refreshToken)) {
            throw new AuthenticationException('Access token expired and no refresh token available.');
        }

        $refreshed = $this->client->fetchAccessTokenWithRefreshToken((string) $refreshToken);
        if (isset($refreshed['error'])) {
            $msg = (string) ($refreshed['error_description'] ?? $refreshed['error']);
            throw new AuthenticationException("Token refresh failed: {$msg}");
        }

        $newToken = $this->client->getAccessToken();
        if (! is_array($newToken)) {
            throw new AuthenticationException('Token refresh returned invalid token shape.');
        }

        Event::dispatch(new TokenRefreshed($token, $newToken));

        return $newToken;
    }
}
