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
}
