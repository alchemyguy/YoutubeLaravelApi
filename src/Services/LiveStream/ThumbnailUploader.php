<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services\LiveStream;

use Alchemyguy\YoutubeLaravelApi\Exceptions\ConfigurationException;
use Google\Client;
use Google\Http\MediaFileUpload;
use Google\Service\YouTube\Resource\Thumbnails;

final class ThumbnailUploader
{
    private const ALLOWED_MIME = ['image/jpeg', 'image/png'];
    private const CHUNK_SIZE_BYTES = 1048576;

    public function __construct(
        private readonly Client $client,
        private readonly Thumbnails $thumbnails,
    ) {}

    public function upload(string $path, string $videoId): string
    {
        if (!is_file($path)) {
            throw new ConfigurationException("Thumbnail file not found: {$path}");
        }
        $mime = (string) (mime_content_type($path) ?: 'application/octet-stream');
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new ConfigurationException(
                "Unsupported thumbnail MIME type '{$mime}'. Allowed: " . implode(', ', self::ALLOWED_MIME)
            );
        }

        $this->client->setDefer(true);
        try {
            $request = $this->thumbnails->set($videoId);
            $media = new MediaFileUpload(
                $this->client,
                $request,
                $mime,
                null,
                true,
                self::CHUNK_SIZE_BYTES
            );
            $media->setFileSize((int) filesize($path));

            $handle = fopen($path, 'rb');
            if ($handle === false) {
                throw new ConfigurationException("Cannot open thumbnail file: {$path}");
            }
            try {
                $status = false;
                while (!$status && !feof($handle)) {
                    $chunk = fread($handle, self::CHUNK_SIZE_BYTES);
                    if ($chunk === false) {
                        throw new ConfigurationException('Failed to read thumbnail chunk');
                    }
                    $status = $media->nextChunk($chunk);
                }
            } finally {
                fclose($handle);
            }

            return (string) ($status['items'][0]['default']['url'] ?? '');
        } finally {
            $this->client->setDefer(false);
        }
    }
}
