<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\Services\AuthenticateService;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Client;
use Google\Service\YouTube;
use Google\Service\YouTube\Resource\Channels;
use Google\Service\YouTube\Resource\LiveBroadcasts;
use Mockery;

final class AuthenticateServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_authenticate_with_code_returns_token_channel_and_live_streaming_status(): void
    {
        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('exchangeCode')->once()->with('code123')
            ->andReturn(['access_token' => 'tok', 'refresh_token' => 'rt']);
        $oauth->shouldReceive('setAccessToken')->once();
        $oauth->shouldReceive('client')->andReturn(Mockery::mock(Client::class));

        $channels = Mockery::mock(Channels::class);
        $channels->shouldReceive('listChannels')->once()->with('snippet', ['mine' => true])
            ->andReturn((object) ['items' => [(object) ['id' => 'UC-mine']]]);

        $broadcasts = Mockery::mock(LiveBroadcasts::class);
        $broadcasts->shouldReceive('listLiveBroadcasts')->once()
            ->with('id', ['mine' => true, 'maxResults' => 1])
            ->andReturn((object) ['items' => []]);

        $youtube = Mockery::mock(YouTube::class);
        $youtube->channels = $channels;
        $youtube->liveBroadcasts = $broadcasts;

        $svc = new AuthenticateService(oauth: $oauth, youtube: $youtube);
        $r = $svc->authenticateWithCode('code123');

        $this->assertSame('tok', $r['token']['access_token']);
        $this->assertSame('UC-mine', $r['channel']['id']);
        $this->assertTrue($r['liveStreamingEnabled']);
    }

    public function test_live_streaming_enabled_false_when_google_returns_specific_error(): void
    {
        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('exchangeCode')->andReturn(['access_token' => 't']);
        $oauth->shouldReceive('setAccessToken');
        $oauth->shouldReceive('client')->andReturn(Mockery::mock(Client::class));

        $channels = Mockery::mock(Channels::class);
        $channels->shouldReceive('listChannels')->andReturn((object) ['items' => [(object) ['id' => 'UC1']]]);

        $broadcasts = Mockery::mock(LiveBroadcasts::class);
        $broadcasts->shouldReceive('listLiveBroadcasts')->andThrow(
            new \Google\Service\Exception('liveStreamingNotEnabled', 403, null, [['reason' => 'liveStreamingNotEnabled']])
        );

        $youtube = Mockery::mock(YouTube::class);
        $youtube->channels = $channels;
        $youtube->liveBroadcasts = $broadcasts;

        $svc = new AuthenticateService(oauth: $oauth, youtube: $youtube);
        $r = $svc->authenticateWithCode('code');

        $this->assertFalse($r['liveStreamingEnabled']);
    }

    public function test_get_login_url_delegates_to_oauth(): void
    {
        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('client')->andReturn(Mockery::mock(Client::class));
        $oauth->shouldReceive('getLoginUrl')->once()->with('user@example.com', 'chan-1')->andReturn('url');

        $svc = new AuthenticateService(oauth: $oauth, youtube: Mockery::mock(YouTube::class));
        $this->assertSame('url', $svc->getLoginUrl('user@example.com', 'chan-1'));
    }
}
