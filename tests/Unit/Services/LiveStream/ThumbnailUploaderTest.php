<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services\LiveStream;

use Alchemyguy\YoutubeLaravelApi\Exceptions\ConfigurationException;
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\ThumbnailUploader;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Client;
use Google\Service\YouTube\Resource\Thumbnails;
use Mockery;

final class ThumbnailUploaderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_throws_when_thumbnail_file_missing(): void
    {
        $this->expectException(ConfigurationException::class);
        (new ThumbnailUploader(Mockery::mock(Client::class), Mockery::mock(Thumbnails::class)))
            ->upload('/nonexistent.png', 'video-1');
    }

    public function test_rejects_unsupported_mime_type(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'gif');
        file_put_contents($tmp, "GIF89a\x01\x00\x01\x00\x00\xff\xff\xff!\xf9\x04\x00\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;");

        try {
            $this->expectException(ConfigurationException::class);
            $this->expectExceptionMessage('image/gif');
            (new ThumbnailUploader(Mockery::mock(Client::class), Mockery::mock(Thumbnails::class)))
                ->upload($tmp, 'video-1');
        } finally {
            unlink($tmp);
        }
    }

    public function test_resets_defer_even_when_upload_fails(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setDefer')->ordered()->once()->with(true);
        $client->shouldReceive('setDefer')->ordered()->once()->with(false);

        $thumbs = Mockery::mock(Thumbnails::class);
        $thumbs->shouldReceive('set')->andThrow(new \RuntimeException('boom'));

        $this->expectException(\RuntimeException::class);
        try {
            (new ThumbnailUploader($client, $thumbs))
                ->upload(__DIR__ . '/../../../Fixtures/images/test_thumbnail.png', 'video-1');
        } finally {
            // assertions on Mockery expectations confirm setDefer(false) was called
        }
    }
}
