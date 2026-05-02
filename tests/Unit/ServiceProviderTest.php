<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit;

use Alchemyguy\YoutubeLaravelApi\Support\YoutubeClientFactory;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;

final class ServiceProviderTest extends TestCase
{
    public function test_factory_resolves_as_singleton(): void
    {
        $a = $this->app->make(YoutubeClientFactory::class);
        $b = $this->app->make(YoutubeClientFactory::class);
        $this->assertSame($a, $b);
    }

    public function test_factory_uses_youtube_config(): void
    {
        $factory = $this->app->make(YoutubeClientFactory::class);
        $client = $factory->make();
        $this->assertSame('test-client-id', $client->getClientId());
    }

    public function test_publishes_config_under_youtube_config_tag(): void
    {
        $paths = \Illuminate\Support\ServiceProvider::pathsToPublish(
            \Alchemyguy\YoutubeLaravelApi\YoutubeLaravelApiServiceProvider::class,
            'youtube-config'
        );
        $this->assertNotEmpty($paths);
    }
}
