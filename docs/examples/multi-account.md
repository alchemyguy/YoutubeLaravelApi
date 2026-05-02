# Example: Managing multiple YouTube accounts

The default DI flow assumes a single Google client built from `config/youtube.php`. For SaaS scenarios — one set of OAuth client credentials, many user channels — you can build a per-request client.

```php
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\LiveStreamService;
use Alchemyguy\YoutubeLaravelApi\Support\YoutubeClientFactory;
use Google\Client;

$factory = app(YoutubeClientFactory::class);

foreach (Channel::cursor() as $channel) {
    $client = $factory->make();   // fresh client each iteration
    $live = LiveStreamService::withClient($client);

    foreach ($channel->scheduledBroadcasts as $sched) {
        $live->broadcast($channel->youtube_token, $sched->toBroadcastData());
    }
}
```

Each `withClient()` returns an isolated service instance — no token bleed-through.
