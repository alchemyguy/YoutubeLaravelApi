<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Auth;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\Events\TokenRefreshed;
use Alchemyguy\YoutubeLaravelApi\Exceptions\AuthenticationException;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Client;
use Illuminate\Support\Facades\Event;
use Mockery;

final class OAuthServiceTest extends TestCase
{
    #[\Override]
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_login_url_sets_state_and_login_hint(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setState')->once()->with('chan-1');
        $client->shouldReceive('setLoginHint')->once()->with('user@example.com');
        $client->shouldReceive('createAuthUrl')->once()->andReturn('https://accounts.google.com/o/oauth2/v2/auth?x=y');

        $svc = new OAuthService($client);
        $this->assertSame(
            'https://accounts.google.com/o/oauth2/v2/auth?x=y',
            $svc->getLoginUrl('user@example.com', 'chan-1')
        );
    }

    public function test_get_login_url_skips_state_when_channel_id_missing(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldNotReceive('setState');
        $client->shouldReceive('setLoginHint')->once();
        $client->shouldReceive('createAuthUrl')->once()->andReturn('url');
        (new OAuthService($client))->getLoginUrl('user@example.com');
    }

    public function test_exchange_code_returns_token(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('fetchAccessTokenWithAuthCode')->once()->with('code123')
            ->andReturn(['access_token' => 'tok', 'refresh_token' => 'rt']);
        $token = (new OAuthService($client))->exchangeCode('code123');
        $this->assertSame('tok', $token['access_token']);
    }

    public function test_exchange_code_throws_on_error_response(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('fetchAccessTokenWithAuthCode')->once()
            ->andReturn(['error' => 'invalid_grant', 'error_description' => 'bad']);
        $this->expectException(AuthenticationException::class);
        (new OAuthService($client))->exchangeCode('bad');
    }

    public function test_set_access_token_dispatches_event_on_refresh(): void
    {
        Event::fake();
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setAccessToken')->once();
        $client->shouldReceive('isAccessTokenExpired')->andReturn(true, false);
        $client->shouldReceive('getRefreshToken')->andReturn('refresh-tok');
        $client->shouldReceive('fetchAccessTokenWithRefreshToken')->once()->with('refresh-tok')
            ->andReturn(['access_token' => 'new']);
        $client->shouldReceive('getAccessToken')->andReturn(['access_token' => 'new']);

        $svc = new OAuthService($client);
        $newToken = $svc->setAccessToken(['access_token' => 'old', 'refresh_token' => 'refresh-tok']);

        $this->assertSame(['access_token' => 'new'], $newToken);
        Event::assertDispatched(TokenRefreshed::class);
    }

    public function test_set_access_token_returns_null_when_not_refreshed(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setAccessToken')->once();
        $client->shouldReceive('isAccessTokenExpired')->andReturn(false);
        $svc = new OAuthService($client);
        $this->assertNull($svc->setAccessToken(['access_token' => 'still-good']));
    }
}
