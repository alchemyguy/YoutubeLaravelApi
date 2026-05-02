<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\DTOs\BrandingProperties;
use Alchemyguy\YoutubeLaravelApi\Services\ChannelService;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Client;
use Google\Service\YouTube;
use Google\Service\YouTube\Channel;
use Google\Service\YouTube\Resource\Channels;
use Google\Service\YouTube\Resource\Subscriptions;
use Google\Service\YouTube\Subscription;
use Mockery;

final class ChannelServiceTest extends TestCase
{
    #[\Override]
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
        $svc = new class(new OAuthService($client)) extends ChannelService
        {
            public ?YouTube $injected = null;

            protected function youtube(): YouTube
            {
                return $this->injected ?? throw new \LogicException('youtube not injected');
            }
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

        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('setAccessToken')->once();

        $svc = new class($oauth) extends ChannelService
        {
            public ?YouTube $injected = null;

            protected function youtube(): YouTube
            {
                return $this->injected ?? throw new \LogicException('youtube not injected');
            }
        };
        $svc->injected = $youtube;

        $result = $svc->getOwnChannel(['access_token' => 'tok']);
        $this->assertNotNull($result);
        $this->assertSame('UC-mine', $result['id']);
    }

    public function test_get_own_channel_returns_null_when_no_items(): void
    {
        $channelsResource = Mockery::mock(Channels::class);
        $channelsResource->shouldReceive('listChannels')->once()->andReturn((object) ['items' => []]);
        $youtube = Mockery::mock(YouTube::class);
        $youtube->channels = $channelsResource;

        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('setAccessToken');

        $svc = new class($oauth) extends ChannelService
        {
            public ?YouTube $injected = null;

            protected function youtube(): YouTube
            {
                return $this->injected ?? throw new \LogicException('youtube not injected');
            }
        };
        $svc->injected = $youtube;

        $this->assertNull($svc->getOwnChannel(['access_token' => 'tok']));
    }

    public function test_subscriptions_paginates_until_total_results(): void
    {
        $page1 = (object) [
            'items' => [
                (object) ['snippet' => (object) ['resourceId' => (object) ['channelId' => 'A']]],
                (object) ['snippet' => (object) ['resourceId' => (object) ['channelId' => 'B']]],
            ],
            'nextPageToken' => 'tok2',
        ];
        $page2 = (object) [
            'items' => [
                (object) ['snippet' => (object) ['resourceId' => (object) ['channelId' => 'C']]],
            ],
        ];

        $subs = Mockery::mock(Subscriptions::class);
        $subs->shouldReceive('listSubscriptions')->twice()->andReturn($page1, $page2);
        $youtube = Mockery::mock(YouTube::class);
        $youtube->subscriptions = $subs;

        $svc = new class(new OAuthService(Mockery::mock(Client::class))) extends ChannelService
        {
            public ?YouTube $injected = null;

            protected function youtube(): YouTube
            {
                return $this->injected ?? throw new \LogicException('youtube not injected');
            }
        };
        $svc->injected = $youtube;

        $result = $svc->subscriptions(['channelId' => 'UC1', 'totalResults' => 3]);
        $this->assertCount(3, $result);
        $this->assertSame(['A', 'B', 'C'], array_column($result, 'channelId'));
    }

    public function test_subscribe_inserts_subscription(): void
    {
        $subs = Mockery::mock(Subscriptions::class);
        $subs->shouldReceive('insert')->once()->withArgs(fn (string $part, $resource): bool => $part === 'snippet' && $resource instanceof Subscription)->andReturn((object) ['id' => 'sub-1']);

        $youtube = Mockery::mock(YouTube::class);
        $youtube->subscriptions = $subs;

        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('setAccessToken')->once();

        $svc = new class($oauth) extends ChannelService
        {
            public ?YouTube $injected = null;

            protected function youtube(): YouTube
            {
                return $this->injected ?? throw new \LogicException('youtube not injected');
            }
        };
        $svc->injected = $youtube;

        $resp = $svc->subscribe(['access_token' => 'tok'], 'UC-target');
        $this->assertSame('sub-1', $resp['id']);
    }

    public function test_unsubscribe_deletes_subscription(): void
    {
        $subs = Mockery::mock(Subscriptions::class);
        $subs->shouldReceive('delete')->once()->with('sub-id-1')->andReturn(null);

        $youtube = Mockery::mock(YouTube::class);
        $youtube->subscriptions = $subs;

        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('setAccessToken')->once();

        $svc = new class($oauth) extends ChannelService
        {
            public ?YouTube $injected = null;

            protected function youtube(): YouTube
            {
                return $this->injected ?? throw new \LogicException('youtube not injected');
            }
        };
        $svc->injected = $youtube;

        $svc->unsubscribe(['access_token' => 'tok'], 'sub-id-1');
    }

    public function test_update_branding_calls_channels_update_with_resource(): void
    {
        $channels = Mockery::mock(Channels::class);
        $channels->shouldReceive('update')->once()->withArgs(fn ($part, $resource, $params): bool => $part === 'brandingSettings'
            && $resource instanceof Channel)->andReturn((object) ['id' => 'UC1']);

        $youtube = Mockery::mock(YouTube::class);
        $youtube->channels = $channels;

        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('setAccessToken')->once();

        $svc = new class($oauth) extends ChannelService
        {
            public ?YouTube $injected = null;

            protected function youtube(): YouTube
            {
                return $this->injected ?? throw new \LogicException('youtube not injected');
            }
        };
        $svc->injected = $youtube;

        $props = new BrandingProperties(
            channelId: 'UC1',
            description: 'new desc',
            keywords: 'a,b',
        );
        $svc->updateBranding(['access_token' => 'tok'], $props);
    }
}
