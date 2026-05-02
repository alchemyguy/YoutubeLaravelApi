<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services;

use Alchemyguy\YoutubeLaravelApi\Services\ChannelService;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Client;
use Google\Service\YouTube;
use Google\Service\YouTube\Resource\Channels;
use Mockery;

final class ChannelServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_list_by_id_calls_youtube_channels_list(): void
    {
        $channelsResource = Mockery::mock(Channels::class);
        $channelsResource->shouldReceive('listChannels')
            ->once()
            ->with('id,snippet', ['id' => 'UC1,UC2'])
            ->andReturn(['items' => [['id' => 'UC1'], ['id' => 'UC2']]]);

        $youtube = Mockery::mock(YouTube::class);
        $youtube->channels = $channelsResource;

        $client = Mockery::mock(Client::class);
        $svc = new class(new \Alchemyguy\YoutubeLaravelApi\Auth\OAuthService($client)) extends ChannelService {
            public ?YouTube $injected = null;
            protected function youtube(): YouTube { return $this->injected; }
        };
        $svc->injected = $youtube;

        $result = $svc->listById(['id' => 'UC1,UC2'], 'id,snippet');
        $this->assertSame('UC1', $result['items'][0]['id']);
    }

    public function test_get_own_channel_authorizes_and_returns_first_item(): void
    {
        $channelsResource = Mockery::mock(Channels::class);
        $channelsResource->shouldReceive('listChannels')
            ->once()
            ->with('snippet,contentDetails,statistics,brandingSettings', ['mine' => true])
            ->andReturn((object) ['items' => [(object) ['id' => 'UC-mine']]]);

        $youtube = Mockery::mock(YouTube::class);
        $youtube->channels = $channelsResource;

        $oauth = Mockery::mock(\Alchemyguy\YoutubeLaravelApi\Auth\OAuthService::class);
        $oauth->shouldReceive('setAccessToken')->once();

        $svc = new class($oauth) extends ChannelService {
            public ?YouTube $injected = null;
            protected function youtube(): YouTube { return $this->injected; }
        };
        $svc->injected = $youtube;

        $result = $svc->getOwnChannel(['access_token' => 'tok']);
        $this->assertSame('UC-mine', $result['id']);
    }

    public function test_get_own_channel_returns_null_when_no_items(): void
    {
        $channelsResource = Mockery::mock(Channels::class);
        $channelsResource->shouldReceive('listChannels')->once()->andReturn((object) ['items' => []]);
        $youtube = Mockery::mock(YouTube::class);
        $youtube->channels = $channelsResource;

        $oauth = Mockery::mock(\Alchemyguy\YoutubeLaravelApi\Auth\OAuthService::class);
        $oauth->shouldReceive('setAccessToken');

        $svc = new class($oauth) extends ChannelService {
            public ?YouTube $injected = null;
            protected function youtube(): YouTube { return $this->injected; }
        };
        $svc->injected = $youtube;

        $this->assertNull($svc->getOwnChannel(['access_token' => 'tok']));
    }
}
