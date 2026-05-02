<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Support;

use Alchemyguy\YoutubeLaravelApi\Exceptions\ConfigurationException;
use Alchemyguy\YoutubeLaravelApi\Support\YoutubeClientFactory;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Client;

final class YoutubeClientFactoryTest extends TestCase
{
    public function test_make_returns_configured_google_client(): void
    {
        $factory = new YoutubeClientFactory([
            'app_name' => 'TestApp',
            'client_id' => 'cid',
            'client_secret' => 'csec',
            'api_key' => 'k',
            'redirect_url' => 'http://localhost/cb',
        ]);

        $client = $factory->make();

        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame('cid', $client->getClientId());
        $this->assertSame('http://localhost/cb', $client->getRedirectUri());
        $this->assertContains('https://www.googleapis.com/auth/youtube', $client->getScopes());
    }

    public function test_make_throws_when_required_keys_missing(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('client_id');

        (new YoutubeClientFactory(['client_secret' => 'csec', 'redirect_url' => 'http://x']))->make();
    }

    public function test_make_sets_offline_access_and_consent_prompt(): void
    {
        $factory = new YoutubeClientFactory([
            'client_id' => 'cid',
            'client_secret' => 'csec',
            'redirect_url' => 'http://localhost/cb',
        ]);
        $client = $factory->make();
        $authUrl = $client->createAuthUrl();
        $this->assertStringContainsString('access_type=offline', $authUrl);
        $this->assertStringContainsString('prompt=consent', $authUrl);
    }
}
