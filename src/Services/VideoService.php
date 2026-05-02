<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services;

use Alchemyguy\YoutubeLaravelApi\DTOs\VideoUploadData;
use Alchemyguy\YoutubeLaravelApi\Enums\Rating;
use Alchemyguy\YoutubeLaravelApi\Exceptions\ConfigurationException;
use Google\Http\MediaFileUpload;
use Google\Service\YouTube;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoSnippet;
use Google\Service\YouTube\VideoStatus;

class VideoService extends BaseService
{
    protected function youtube(): YouTube
    {
        return new YouTube($this->client());
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function listById(array $params, string $part = 'snippet,contentDetails,id,statistics'): array
    {
        $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
        return $this->call(fn () => (array) $this->youtube()->videos->listVideos($part, $params));
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function search(array $params, string $part = 'snippet,id'): array
    {
        $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
        return $this->call(fn () => (array) $this->youtube()->search->listSearch($part, $params));
    }

    /** @param array<string, mixed> $token */
    public function delete(array $token, string $videoId): void
    {
        $this->authorize($token);
        $this->call(fn () => $this->youtube()->videos->delete($videoId));
    }

    /** @param array<string, mixed> $token */
    public function rate(array $token, string $videoId, Rating $rating): void
    {
        $this->authorize($token);
        $this->call(fn () => $this->youtube()->videos->rate($videoId, $rating->value));
    }

    /**
     * Upload a video using a resumable, chunked upload.
     *
     * @param array<string, mixed> $token
     * @return array<string, mixed>
     */
    public function upload(array $token, string $videoPath, VideoUploadData $data): array
    {
        if (!is_file($videoPath)) {
            throw new ConfigurationException("Video file not found: {$videoPath}");
        }

        $this->authorize($token);
        $client = $this->client();

        $snippet = new VideoSnippet();
        $snippet->setTitle($data->title);
        $snippet->setDescription($data->description);
        $snippet->setTags($data->tags);
        $snippet->setCategoryId($data->categoryId);

        $status = new VideoStatus();
        $status->setPrivacyStatus($data->privacyStatus->value);

        $video = new Video();
        $video->setSnippet($snippet);
        $video->setStatus($status);

        $client->setDefer(true);
        try {
            return $this->call(function () use ($client, $videoPath, $data, $video): array {
                $insertRequest = $this->youtube()->videos->insert('status,snippet', $video);
                $media = new MediaFileUpload(
                    $client,
                    $insertRequest,
                    'video/*',
                    null,
                    true,
                    $data->chunkSizeBytes
                );
                $media->setFileSize((int) filesize($videoPath));

                $handle = fopen($videoPath, 'rb');
                if ($handle === false) {
                    throw new ConfigurationException("Cannot open video file: {$videoPath}");
                }
                try {
                    $status = false;
                    while (!$status && !feof($handle)) {
                        $chunk = fread($handle, $data->chunkSizeBytes);
                        if ($chunk === false) {
                            throw new ConfigurationException('Failed to read video chunk');
                        }
                        $status = $media->nextChunk($chunk);
                    }
                } finally {
                    fclose($handle);
                }
                return is_array($status) ? $status : (array) json_decode(json_encode($status, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
            });
        } finally {
            $client->setDefer(false);
        }
    }
}
