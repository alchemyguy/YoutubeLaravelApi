<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services\LiveStream;

use Alchemyguy\YoutubeLaravelApi\DTOs\BroadcastData;
use Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus;
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\BroadcastManager;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use DateTimeImmutable;
use Google\Service\YouTube\LiveBroadcast;
use Google\Service\YouTube\Resource\LiveBroadcasts;
use Mockery;

final class BroadcastManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_insert_calls_live_broadcasts_insert(): void
    {
        $broadcasts = Mockery::mock(LiveBroadcasts::class);
        $broadcasts->shouldReceive('insert')->once()->withArgs(function ($part, $resource, $params) {
            return $part === 'snippet,status'
                && $resource instanceof LiveBroadcast;
        })->andReturn((object) ['id' => 'evt-1']);

        $data = new BroadcastData(
            title: 'Live Title',
            description: 'desc',
            scheduledStartTime: new DateTimeImmutable('+1 hour'),
            privacyStatus: PrivacyStatus::Public,
        );

        $resp = (new BroadcastManager($broadcasts))->insert($data);
        $this->assertSame('evt-1', $resp['id']);
    }

    /** @bug regression for LiveStreamService::transitionEvent (Section 6, bug 1) */
    public function test_transition_actually_transitions_when_token_is_present(): void
    {
        $broadcasts = Mockery::mock(LiveBroadcasts::class);
        $broadcasts->shouldReceive('transition')
            ->once()
            ->with('live', 'evt-1', 'status,id,snippet')
            ->andReturn((object) ['lifeCycleStatus' => 'live']);

        $resp = (new BroadcastManager($broadcasts))->transition(
            'evt-1',
            \Alchemyguy\YoutubeLaravelApi\Enums\BroadcastStatus::Live
        );
        $this->assertSame('live', $resp['lifeCycleStatus']);
    }

    public function test_transition_throws_on_empty_broadcast_id(): void
    {
        $this->expectException(\Alchemyguy\YoutubeLaravelApi\Exceptions\ConfigurationException::class);
        (new BroadcastManager(Mockery::mock(LiveBroadcasts::class)))
            ->transition('', \Alchemyguy\YoutubeLaravelApi\Enums\BroadcastStatus::Live);
    }

    public function test_delete_calls_live_broadcasts_delete(): void
    {
        $broadcasts = Mockery::mock(LiveBroadcasts::class);
        $broadcasts->shouldReceive('delete')->once()->with('evt-1');
        (new BroadcastManager($broadcasts))->delete('evt-1');
    }
}
