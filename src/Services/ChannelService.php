<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services;

use Google\Service\YouTube;

class ChannelService extends BaseService
{
    protected function youtube(): YouTube
    {
        return new YouTube($this->client());
    }

    /**
     * Public, no-token lookup. $params accepts 'id' (comma-separated) or 'forUsername'.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function listById(array $params, string $part = 'id,snippet'): array
    {
        $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
        return $this->call(fn () => (array) $this->youtube()->channels->listChannels($part, $params));
    }

    /**
     * @param array<string, mixed> $token
     * @return array<string, mixed>|null
     */
    public function getOwnChannel(array $token): ?array
    {
        $this->authorize($token);

        return $this->call(function (): ?array {
            $response = $this->youtube()->channels->listChannels(
                'snippet,contentDetails,statistics,brandingSettings',
                ['mine' => true]
            );
            $decoded = json_decode(json_encode($response, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
            return $decoded['items'][0] ?? null;
        });
    }

    /**
     * @param array<string, mixed> $params Required: 'channelId', 'totalResults'
     * @return array<int, array<string, mixed>>
     */
    public function subscriptions(array $params, string $part = 'snippet'): array
    {
        $channelId = (string) ($params['channelId'] ?? '');
        $totalResults = max(0, (int) ($params['totalResults'] ?? 0));
        $perPage = 50;

        return $this->call(function () use ($channelId, $totalResults, $perPage, $part): array {
            $youtube = $this->youtube();
            $collected = [];
            $pageToken = null;

            do {
                $req = ['channelId' => $channelId, 'maxResults' => $perPage];
                if ($pageToken !== null) {
                    $req['pageToken'] = $pageToken;
                }
                $resp = $youtube->subscriptions->listSubscriptions($part, $req);
                $decoded = json_decode(json_encode($resp, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
                foreach ($decoded['items'] ?? [] as $item) {
                    $collected[] = $item['snippet']['resourceId'] ?? [];
                    if (count($collected) >= $totalResults) {
                        return $collected;
                    }
                }
                $pageToken = $decoded['nextPageToken'] ?? null;
            } while ($pageToken !== null && count($collected) < $totalResults);

            return $collected;
        });
    }

    /**
     * Subscribe the authorized channel to the target channel.
     *
     * NOTE: YouTube heavily rate-limits subscription writes (anti-bot). Expect
     * frequent rejections for non-interactive automation.
     *
     * @param array<string, mixed> $token
     * @return array<string, mixed>
     */
    public function subscribe(array $token, string $targetChannelId): array
    {
        $this->authorize($token);
        return $this->call(function () use ($targetChannelId): array {
            $resource = new \Google\Service\YouTube\Subscription([
                'snippet' => [
                    'resourceId' => [
                        'kind' => 'youtube#channel',
                        'channelId' => $targetChannelId,
                    ],
                ],
            ]);
            $resp = $this->youtube()->subscriptions->insert('snippet', $resource);
            return (array) json_decode(json_encode($resp, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        });
    }

    /** @param array<string, mixed> $token */
    public function unsubscribe(array $token, string $subscriptionId): void
    {
        $this->authorize($token);
        $this->call(fn () => $this->youtube()->subscriptions->delete($subscriptionId));
    }
}
