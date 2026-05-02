# Authentication

The package uses Google's OAuth 2.0 with `access_type=offline` so you receive a refresh token along with the access token.

## Step 1: Generate the login URL

```php
use Alchemyguy\YoutubeLaravelApi\Services\AuthenticateService;

$auth = app(AuthenticateService::class);

$url = $auth->getLoginUrl(
    youtubeEmail: 'creator@example.com',
    channelId: 'your-internal-channel-identifier'
);

return redirect($url);
```

The `channelId` is round-tripped via OAuth `state` so you can correlate the callback with your application's record.

## Step 2: Handle the callback

```php
public function callback(Request $request, AuthenticateService $auth)
{
    $code = $request->query('code');
    $identifier = $request->query('state');

    $result = $auth->authenticateWithCode($code);

    // Persist somewhere durable
    Channel::find($identifier)->update([
        'youtube_token' => $result['token'],
        'youtube_channel_id' => $result['channel']['id'] ?? null,
        'live_streaming_enabled' => $result['liveStreamingEnabled'],
    ]);
}
```

`$result` is shaped:

```php
[
    'token' => ['access_token' => '…', 'refresh_token' => '…', 'expires_in' => 3600, …],
    'channel' => ['id' => 'UC…', 'snippet' => […]],   // null if no channel
    'liveStreamingEnabled' => true|false,
]
```

## Token refresh

Whenever a service applies an expired token, it automatically refreshes via the stored refresh token. The package dispatches a `TokenRefreshed` event so you can persist the new token:

```php
use Alchemyguy\YoutubeLaravelApi\Events\TokenRefreshed;
use Illuminate\Support\Facades\Event;

Event::listen(function (TokenRefreshed $event) {
    Channel::where('youtube_token->access_token', $event->oldToken['access_token'])
        ->update(['youtube_token' => $event->newToken]);
});
```

You can also persist eagerly: every service method that takes a `$token` returns implicitly via the event, but for explicit handling you can call `OAuthService::setAccessToken($token)` yourself and persist its return value.
