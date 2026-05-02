<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services\LiveStream;

use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\StreamManager;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Service\YouTube\LiveStream;
use Google\Service\YouTube\Resource\LiveBroadcasts;
use Google\Service\YouTube\Resource\LiveStreams;
use Mockery;

final class StreamManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_insert_creates_stream_with_rtmp_720p(): void
    {
        $streams = Mockery::mock(LiveStreams::class);
        $streams->shouldReceive('insert')->once()->withArgs(function ($part, $resource, $params) {
            return $part === 'snippet,cdn' && $resource instanceof LiveStream;
        })->andReturn((object) ['id' => 'stream-1']);

        $broadcasts = Mockery::mock(LiveBroadcasts::class);

        $r = (new StreamManager($streams, $broadcasts))->insert('Title');
        $this->assertSame('stream-1', $r['id']);
    }

    public function test_bind_links_broadcast_to_stream(): void
    {
        $streams = Mockery::mock(LiveStreams::class);
        $broadcasts = Mockery::mock(LiveBroadcasts::class);
        $broadcasts->shouldReceive('bind')->once()
            ->with('evt-1', 'id,contentDetails', ['streamId' => 'stream-1'])
            ->andReturn((object) ['id' => 'evt-1']);

        $r = (new StreamManager($streams, $broadcasts))->bind('evt-1', 'stream-1');
        $this->assertSame('evt-1', $r['id']);
    }
}
