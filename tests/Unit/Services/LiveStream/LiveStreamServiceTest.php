<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services\LiveStream;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\DTOs\BroadcastData;
use Alchemyguy\YoutubeLaravelApi\Enums\BroadcastStatus;
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\BroadcastManager;
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\LiveStreamService;
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\StreamManager;
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\ThumbnailUploader;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use DateTimeImmutable;
use Google\Client;
use Google\Service\YouTube;
use Google\Service\YouTube\Resource\Videos;
use Mockery;

final class LiveStreamServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_broadcast_orchestrates_insert_metadata_stream_bind(): void
    {
        $broadcasts = Mockery::mock(BroadcastManager::class);
        $broadcasts->shouldReceive('insert')->once()->andReturn(['id' => 'evt-1']);

        $streams = Mockery::mock(StreamManager::class);
        $streams->shouldReceive('insert')->once()->andReturn(['id' => 'stream-1', 'cdn' => ['ingestionInfo' => ['ingestionAddress' => 'rtmp://x', 'streamName' => 'k']]]);
        $streams->shouldReceive('bind')->once()->with('evt-1', 'stream-1')->andReturn(['id' => 'evt-1']);

        $videos = Mockery::mock(Videos::class);
        $videos->shouldReceive('listVideos')->once()->andReturn(['items' => [['snippet' => ['tags' => []]]]]);
        $videos->shouldReceive('update')->once()->andReturn(['id' => 'evt-1']);
        $youtube = Mockery::mock(YouTube::class);
        $youtube->videos = $videos;

        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('setAccessToken')->once();
        $oauth->shouldReceive('client')->andReturn(Mockery::mock(Client::class));

        $svc = new LiveStreamService(
            oauth: $oauth,
            broadcasts: $broadcasts,
            streams: $streams,
            thumbnails: Mockery::mock(ThumbnailUploader::class),
            youtube: $youtube,
            languages: ['English' => 'en'],
        );

        $data = new BroadcastData(
            title: 'T',
            description: 'D',
            scheduledStartTime: new DateTimeImmutable('+1 hour'),
        );
        $resp = $svc->broadcast(['access_token' => 'tok'], $data);

        $this->assertSame('evt-1', $resp['broadcast']['id']);
        $this->assertSame('stream-1', $resp['stream']['id']);
        $this->assertSame('rtmp://x', $resp['stream']['cdn']['ingestionInfo']['ingestionAddress']);
    }

    public function test_transition_delegates_to_broadcast_manager(): void
    {
        $broadcasts = Mockery::mock(BroadcastManager::class);
        $broadcasts->shouldReceive('transition')->once()->with('evt-1', BroadcastStatus::Live)
            ->andReturn(['lifeCycleStatus' => 'live']);

        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('setAccessToken')->once();
        $oauth->shouldReceive('client')->andReturn(Mockery::mock(Client::class));

        $svc = new LiveStreamService(
            oauth: $oauth,
            broadcasts: $broadcasts,
            streams: Mockery::mock(StreamManager::class),
            thumbnails: Mockery::mock(ThumbnailUploader::class),
            youtube: Mockery::mock(YouTube::class),
            languages: [],
        );

        $r = $svc->transition(['access_token' => 'tok'], 'evt-1', BroadcastStatus::Live);
        $this->assertSame('live', $r['lifeCycleStatus']);
    }
}
