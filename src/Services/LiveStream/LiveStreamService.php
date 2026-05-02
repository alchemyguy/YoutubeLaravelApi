<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services\LiveStream;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\DTOs\BroadcastData;
use Alchemyguy\YoutubeLaravelApi\Enums\BroadcastStatus;
use Alchemyguy\YoutubeLaravelApi\Exceptions\YoutubeApiException;
use Alchemyguy\YoutubeLaravelApi\Services\BaseService;
use Google\Service\YouTube;
use Google\Service\YouTube\Video;

class LiveStreamService extends BaseService
{
    private readonly BroadcastManager $broadcasts;

    private readonly StreamManager $streams;

    private readonly ThumbnailUploader $thumbnails;

    private readonly YouTube $youtube;

    /** @var array<string, string> */
    private array $languages;

    /**
     * @param array<string, string> $languages
     */
    public function __construct(
        ?OAuthService $oauth = null,
        ?BroadcastManager $broadcasts = null,
        ?StreamManager $streams = null,
        ?ThumbnailUploader $thumbnails = null,
        ?YouTube $youtube = null,
        ?array $languages = null,
    ) {
        parent::__construct($oauth);
        $client = $this->client();
        $this->youtube = $youtube ?? new YouTube($client);
        $this->broadcasts = $broadcasts ?? new BroadcastManager($this->youtube->liveBroadcasts);
        $this->streams = $streams ?? new StreamManager($this->youtube->liveStreams, $this->youtube->liveBroadcasts);
        $this->thumbnails = $thumbnails ?? new ThumbnailUploader($client, $this->youtube->thumbnails);
        $this->languages = $languages ?? (array) config('youtube.languages', []);
    }

    /**
     * @param array<string, mixed> $token
     * @return array{broadcast: array<string, mixed>, stream: array<string, mixed>, binding: array<string, mixed>}
     */
    public function broadcast(array $token, BroadcastData $data): array
    {
        $this->authorize($token);

        return $this->call(function () use ($data): array {
            $broadcast = $this->broadcasts->insert($data);
            $broadcastId = (string) ($broadcast['id'] ?? '');
            if ($broadcastId === '') {
                throw new YoutubeApiException('Broadcast insert returned no id.');
            }

            if ($data->thumbnailPath !== null) {
                $this->thumbnails->upload($data->thumbnailPath, $broadcastId);
            }

            $this->updateVideoMetadata($broadcastId, $data);

            $stream = $this->streams->insert($data->title);
            $streamId = (string) ($stream['id'] ?? '');
            if ($streamId === '') {
                throw new YoutubeApiException('Stream insert returned no id.');
            }

            $binding = $this->streams->bind($broadcastId, $streamId);

            return [
                'broadcast' => $broadcast,
                'stream' => $stream,
                'binding' => $binding,
            ];
        });
    }

    /**
     * @param array<string, mixed> $token
     * @return array{broadcast: array<string, mixed>, stream: array<string, mixed>, binding: array<string, mixed>}
     */
    public function updateBroadcast(array $token, string $broadcastId, BroadcastData $data): array
    {
        $this->authorize($token);

        return $this->call(function () use ($broadcastId, $data): array {
            $broadcast = $this->broadcasts->update($broadcastId, $data);

            if ($data->thumbnailPath !== null) {
                $this->thumbnails->upload($data->thumbnailPath, $broadcastId);
            }
            $this->updateVideoMetadata($broadcastId, $data);

            $stream = $this->streams->insert($data->title);
            $streamId = (string) ($stream['id'] ?? '');
            $binding = $this->streams->bind($broadcastId, $streamId);

            return [
                'broadcast' => $broadcast,
                'stream' => $stream,
                'binding' => $binding,
            ];
        });
    }

    /**
     * @param array<string, mixed> $token
     * @return array<string, mixed>
     */
    public function transition(array $token, string $broadcastId, BroadcastStatus $status): array
    {
        $this->authorize($token);

        return $this->call(fn (): array => $this->broadcasts->transition($broadcastId, $status));
    }

    /** @param array<string, mixed> $token */
    public function delete(array $token, string $broadcastId): void
    {
        $this->authorize($token);
        $this->call(fn () => $this->broadcasts->delete($broadcastId));
    }

    /**
     * Read-back-after-write with bounded retry to handle eventual consistency.
     * Bug #15 fix.
     */
    private function updateVideoMetadata(string $videoId, BroadcastData $data): void
    {
        $videoSnippet = $this->fetchVideoSnippetWithRetry($videoId);
        $videoSnippet['tags'] = $data->tags;
        $lang = $this->languages[$data->languageName] ?? 'en';
        $videoSnippet['defaultAudioLanguage'] = $lang;
        $videoSnippet['defaultLanguage'] = $lang;
        $videoSnippet['title'] = $data->title;
        $videoSnippet['description'] = $data->description;

        $this->youtube->videos->update('snippet', new Video([
            'id' => $videoId,
            'snippet' => $videoSnippet,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchVideoSnippetWithRetry(string $videoId): array
    {
        $attempts = 0;
        $delayMs = 500;
        while ($attempts < 3) {
            $resp = $this->youtube->videos->listVideos('snippet', ['id' => $videoId]);
            $decoded = is_array($resp)
                ? $resp
                : (array) json_decode(json_encode($resp, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
            $items = $decoded['items'] ?? [];
            if (! empty($items)) {
                return (array) ($items[0]['snippet'] ?? []);
            }
            $attempts++;
            usleep($delayMs * 1000);
        }
        throw new YoutubeApiException("Video {$videoId} not visible after retries.");
    }
}
