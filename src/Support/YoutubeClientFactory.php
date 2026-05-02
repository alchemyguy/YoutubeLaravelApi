<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Support;

use Alchemyguy\YoutubeLaravelApi\Exceptions\ConfigurationException;
use Google\Client;

final readonly class YoutubeClientFactory
{
    /** @param array<string, mixed> $config */
    public function __construct(private array $config) {}

    public function make(): Client
    {
        $this->assertRequired(['client_id', 'client_secret', 'redirect_url']);

        $client = new Client;
        $client->setApplicationName((string) ($this->config['app_name'] ?? 'YoutubeLaravelApi'));
        $client->setClientId((string) $this->config['client_id']);
        $client->setClientSecret((string) $this->config['client_secret']);
        $client->setRedirectUri((string) $this->config['redirect_url']);

        if (! empty($this->config['api_key'])) {
            $client->setDeveloperKey((string) $this->config['api_key']);
        }

        $client->setScopes(['https://www.googleapis.com/auth/youtube']);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    /** @param list<string> $keys */
    private function assertRequired(array $keys): void
    {
        foreach ($keys as $key) {
            if (empty($this->config[$key])) {
                throw new ConfigurationException(
                    "Missing required YouTube config key: {$key}. Set YOUTUBE_" . strtoupper($key) . ' in your .env.'
                );
            }
        }
    }
}
