<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\DTOs;

use Alchemyguy\YoutubeLaravelApi\DTOs\BroadcastData;
use Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class BroadcastDataTest extends TestCase
{
    public function test_constructs_with_minimal_fields(): void
    {
        $start = new DateTimeImmutable('+1 hour');
        $data = new BroadcastData(
            title: 'Hello',
            description: 'World',
            scheduledStartTime: $start,
        );
        $this->assertSame('Hello', $data->title);
        $this->assertSame('World', $data->description);
        $this->assertSame(PrivacyStatus::Public, $data->privacyStatus);
        $this->assertSame('English', $data->languageName);
        $this->assertSame([], $data->tags);
    }

    public function test_throws_when_start_is_in_past(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('past');
        new BroadcastData(
            title: 'X',
            description: 'Y',
            scheduledStartTime: new DateTimeImmutable('-1 hour'),
        );
    }

    public function test_throws_when_end_before_start(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('end');
        new BroadcastData(
            title: 'X',
            description: 'Y',
            scheduledStartTime: new DateTimeImmutable('+2 hour'),
            scheduledEndTime: new DateTimeImmutable('+1 hour'),
        );
    }

    public function test_throws_when_tag_total_length_exceeds_500(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('500');
        new BroadcastData(
            title: 'X',
            description: 'Y',
            scheduledStartTime: new DateTimeImmutable('+1 hour'),
            tags: [str_repeat('a', 501)],
        );
    }

    public function test_from_array_constructs_dto(): void
    {
        $data = BroadcastData::fromArray([
            'title' => 'X',
            'description' => 'Y',
            'event_start_date_time' => (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'),
            'time_zone' => 'UTC',
            'privacy_status' => 'private',
            'language_name' => 'French',
            'tag_array' => ['x', 'y'],
        ]);
        $this->assertSame('X', $data->title);
        $this->assertSame(PrivacyStatus::Private, $data->privacyStatus);
        $this->assertSame('French', $data->languageName);
        $this->assertSame(['x', 'y'], $data->tags);
    }
}
