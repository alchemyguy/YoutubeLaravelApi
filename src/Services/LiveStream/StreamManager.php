<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services\LiveStream;

use Google\Service\YouTube\CdnSettings;
use Google\Service\YouTube\LiveStream;
use Google\Service\YouTube\LiveStreamSnippet;
use Google\Service\YouTube\Resource\LiveBroadcasts;
use Google\Service\YouTube\Resource\LiveStreams;

class StreamManager
{
    public function __construct(
        private readonly LiveStreams $streams,
        private readonly LiveBroadcasts $broadcasts,
    ) {}

    /** @return array<string, mixed> */
    public function insert(string $title, string $format = '720p', string $ingestionType = 'rtmp'): array
    {
        $snippet = new LiveStreamSnippet();
        $snippet->setTitle($title);

        $cdn = new CdnSettings();
        $cdn->setFormat($format);
        $cdn->setIngestionType($ingestionType);

        $stream = new LiveStream();
        $stream->setSnippet($snippet);
        $stream->setCdn($cdn);
        $stream->setKind('youtube#liveStream');

        $resp = $this->streams->insert('snippet,cdn', $stream, []);
        return $this->decode($resp);
    }

    /** @return array<string, mixed> */
    public function bind(string $broadcastId, string $streamId): array
    {
        $resp = $this->broadcasts->bind(
            $broadcastId,
            'id,contentDetails',
            ['streamId' => $streamId]
        );
        return $this->decode($resp);
    }

    /**
     * @param mixed $resp
     * @return array<string, mixed>
     */
    private function decode($resp): array
    {
        if (is_array($resp)) {
            return $resp;
        }
        return (array) json_decode(json_encode($resp, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    }
}
