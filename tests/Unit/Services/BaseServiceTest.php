<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\Exceptions\QuotaExceededException;
use Alchemyguy\YoutubeLaravelApi\Exceptions\YoutubeApiException;
use Alchemyguy\YoutubeLaravelApi\Services\BaseService;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Client;
use Google\Service\Exception;
use Mockery;

final class BaseServiceTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_constructs_with_explicit_oauth_service(): void
    {
        $oauth = Mockery::mock(OAuthService::class);
        $svc = new class($oauth) extends BaseService {};
        $this->assertSame($oauth, $svc->oauth());
    }

    public function test_resolves_oauth_from_container_when_no_args(): void
    {
        $svc = new class extends BaseService {};
        $this->assertInstanceOf(OAuthService::class, $svc->oauth());
    }

    public function test_with_client_creates_isolated_instance(): void
    {
        $client = Mockery::mock(Client::class);
        $concrete = new class extends BaseService {};
        $built = $concrete::withClient($client);
        $this->assertSame($client, $built->client());
    }

    public function test_call_wraps_google_quota_exception_as_quota_exceeded(): void
    {
        $svc = new class extends BaseService
        {
            public function run(\Closure $fn): mixed
            {
                return $this->call($fn);
            }
        };

        $this->expectException(QuotaExceededException::class);
        $svc->run(fn () => throw new Exception(
            'quota exceeded', 403, null, [['reason' => 'quotaExceeded']]
        ));
    }

    public function test_call_wraps_other_throwables_as_youtube_api_exception(): void
    {
        $svc = new class extends BaseService
        {
            public function run(\Closure $fn): mixed
            {
                return $this->call($fn);
            }
        };

        $this->expectException(YoutubeApiException::class);
        $svc->run(fn () => throw new \RuntimeException('boom'));
    }
}
