<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\Exceptions\YoutubeApiException;
use Alchemyguy\YoutubeLaravelApi\Support\YoutubeClientFactory;
use Closure;
use Google\Client;
use Google\Service\Exception as GoogleServiceException;
use Throwable;

/**
 * @phpstan-consistent-constructor
 */
abstract class BaseService
{
    protected OAuthService $oauth;

    public function __construct(?OAuthService $oauth = null)
    {
        $this->oauth = $oauth ?? new OAuthService(
            app(YoutubeClientFactory::class)->make()
        );
    }

    public static function withClient(Client $client): static
    {
        return new static(new OAuthService($client));
    }

    public function oauth(): OAuthService
    {
        return $this->oauth;
    }

    public function client(): Client
    {
        return $this->oauth->client();
    }

    /**
     * Wrap a Google API call: maps Google\Service\Exception -> YoutubeApiException
     * (and subclasses), preserving the previous chain.
     */
    protected function call(Closure $fn): mixed
    {
        try {
            return $fn();
        } catch (GoogleServiceException $e) {
            throw YoutubeApiException::fromGoogleException($e);
        } catch (Throwable $e) {
            throw new YoutubeApiException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Apply the access token to the client before a call.
     * Returns refreshed token (if any) so callers can choose to persist it.
     *
     * @param array<string, mixed> $token
     * @return array<string, mixed>|null refreshed token, or null if no refresh occurred
     */
    protected function authorize(array $token): ?array
    {
        return $this->oauth->setAccessToken($token);
    }
}
