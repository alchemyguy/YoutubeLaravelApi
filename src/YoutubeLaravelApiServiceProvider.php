<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi;

use Alchemyguy\YoutubeLaravelApi\Support\YoutubeClientFactory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;

class YoutubeLaravelApiServiceProvider extends ServiceProvider
{
    #[\Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/youtube.php', 'youtube');

        $this->app->singleton(YoutubeClientFactory::class, function ($app): YoutubeClientFactory {
            /** @var Repository $config */
            $config = $app->make('config');

            return new YoutubeClientFactory($config->get('youtube', []));
        });
    }

    public function boot(): void
    {
        $this->publishes(
            [__DIR__ . '/config/youtube.php' => config_path('youtube.php')],
            'youtube-config'
        );
    }
}
