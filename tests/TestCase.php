<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests;

use Alchemyguy\YoutubeLaravelApi\YoutubeLaravelApiServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [YoutubeLaravelApiServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('youtube', [
            'app_name'     => 'TestApp',
            'client_id'    => 'test-client-id',
            'client_secret'=> 'test-client-secret',
            'api_key'      => 'test-api-key',
            'redirect_url' => 'http://localhost/callback',
            'languages'    => ['English' => 'en', 'French' => 'fr'],
        ]);
    }
}
