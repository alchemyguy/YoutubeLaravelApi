<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services;

use Google\Service\YouTube;

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
}
