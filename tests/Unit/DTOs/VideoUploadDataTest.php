<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\DTOs;

use Alchemyguy\YoutubeLaravelApi\DTOs\VideoUploadData;
use Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class VideoUploadDataTest extends TestCase
{
    public function test_constructs_with_required_fields(): void
    {
        $d = new VideoUploadData(
            title: 't',
            description: 'd',
            categoryId: '22',
            privacyStatus: PrivacyStatus::Private,
            tags: ['a', 'b'],
        );
        $this->assertSame('t', $d->title);
        $this->assertSame(PrivacyStatus::Private, $d->privacyStatus);
        $this->assertSame(1024 * 1024, $d->chunkSizeBytes);
    }

    public function test_throws_on_chunk_size_below_256kb(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VideoUploadData('t', 'd', '22', PrivacyStatus::Public, [], 1024);
    }

    public function test_from_array_translates_1x_keys(): void
    {
        $d = VideoUploadData::fromArray([
            'title' => 't',
            'description' => 'd',
            'tags' => ['a'],
            'category_id' => '22',
            'video_status' => 'unlisted',
        ]);
        $this->assertSame(PrivacyStatus::Unlisted, $d->privacyStatus);
        $this->assertSame(['a'], $d->tags);
    }
}
