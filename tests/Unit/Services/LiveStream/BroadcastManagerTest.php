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
}
