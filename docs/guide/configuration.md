# Configuration

## Where credentials come from

The package reads credentials from `config/youtube.php`, which by default falls through to env vars (recommended). You can also hardcode for one-off scripts.

## Getting Google credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com).
2. Create a project (or select an existing one).
3. Enable the **YouTube Data API v3**.
4. Create credentials:
   - **API key** for public read-only calls.
   - **OAuth 2.0 client ID** (type: Web application) for user-authorized calls.
5. Add your callback URL under **Authorized redirect URIs**.
6. Copy `client_id`, `client_secret`, `api_key`, and the callback URL into your `.env`.

## Config file reference

```php
return [
    'app_name'      => env('YOUTUBE_APP_NAME', 'YoutubeLaravelApi'),
    'client_id'     => env('YOUTUBE_CLIENT_ID'),
    'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
    'api_key'       => env('YOUTUBE_API_KEY'),
    'redirect_url'  => env('YOUTUBE_REDIRECT_URL'),
    'languages'     => [/* code map for broadcast metadata */],
];
```

## Customizing the Google client

If you need to override anything about the underlying `Google\Client` (custom HTTP handler, additional scopes, ...), bind a replacement factory in your `AppServiceProvider`:

```php
use Alchemyguy\YoutubeLaravelApi\Support\YoutubeClientFactory;
use Google\Client;

$this->app->extend(YoutubeClientFactory::class, function (YoutubeClientFactory $factory) {
    return new class([]) extends YoutubeClientFactory {
        public function make(): Client {
            $client = parent::make();
            $client->setHttpClient(/* your custom Guzzle client */);
            return $client;
        }
    };
});
```
