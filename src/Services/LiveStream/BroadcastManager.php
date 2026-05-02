<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services\LiveStream;

use Alchemyguy\YoutubeLaravelApi\DTOs\BroadcastData;
use Alchemyguy\YoutubeLaravelApi\Enums\BroadcastStatus;
use Alchemyguy\YoutubeLaravelApi\Exceptions\ConfigurationException;
use Google\Service\YouTube\LiveBroadcast;
use Google\Service\YouTube\LiveBroadcastSnippet;
use Google\Service\YouTube\LiveBroadcastStatus;
use Google\Service\YouTube\Resource\LiveBroadcasts;

class BroadcastManager
{
    public function __construct(private readonly LiveBroadcasts $broadcasts) {}

    /** @return array<string, mixed> */
    public function insert(BroadcastData $data): array
    {
        $broadcast = $this->buildResource($data);
        $resp = $this->broadcasts->insert('snippet,status', $broadcast, []);

        return $this->decode($resp);
    }

    /** @return array<string, mixed> */
    public function update(string $broadcastId, BroadcastData $data): array
    {
        $broadcast = $this->buildResource($data);
        $broadcast->setId($broadcastId);
        $resp = $this->broadcasts->update('snippet,status', $broadcast, []);

        return $this->decode($resp);
    }

    /**
     * Bug #1 fix: was inverted. Now correctly errors when broadcastId is empty.
     *
     * @return array<string, mixed>
     */
    public function transition(string $broadcastId, BroadcastStatus $status): array
    {
        if ($broadcastId === '') {
            throw new ConfigurationException('broadcastId cannot be empty.');
        }
        $resp = $this->broadcasts->transition(
            $status->value,
            $broadcastId,
            'status,id,snippet'
        );

        return $this->decode($resp);
    }

    public function delete(string $broadcastId): void
    {
        $this->broadcasts->delete($broadcastId);
    }

    private function buildResource(BroadcastData $data): LiveBroadcast
    {
        $snippet = new LiveBroadcastSnippet;
        $snippet->setTitle($data->title);
        $snippet->setDescription($data->description);
        $snippet->setScheduledStartTime($data->scheduledStartTime->format(DATE_ATOM));
        if ($data->scheduledEndTime !== null) {
            $snippet->setScheduledEndTime($data->scheduledEndTime->format(DATE_ATOM));
        }

        $status = new LiveBroadcastStatus;
        $status->setPrivacyStatus($data->privacyStatus->value);

        $broadcast = new LiveBroadcast;
        $broadcast->setSnippet($snippet);
        $broadcast->setStatus($status);
        $broadcast->setKind('youtube#liveBroadcast');

        return $broadcast;
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
