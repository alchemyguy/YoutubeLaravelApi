# Upgrading from 1.x to 2.0

::: tip
This page mirrors [UPGRADE.md](https://github.com/alchemyguy/YoutubeLaravelApi/blob/master/UPGRADE.md) in the repository. The two are kept in sync.
:::

## TL;DR

- Bump composer requirement: `"alchemyguy/youtube-laravel-api": "^2.0"`
- PHP 8.3+, Laravel 11+ required
- Re-publish config: `php artisan vendor:publish --tag=youtube-config --force`
- Rename env vars (lowercase → uppercase, `YOUTUBE_` prefix)
- Update namespaced imports (`alchemyguy\` → `Alchemyguy\`)
- Read the per-section migration steps below

## 1. Requirements

| | 1.x | 2.0 |
|---|---|---|
| PHP | 7.0+ | 8.3+ |
| Laravel | 5.x+ | 11.x, 12.x |
| google/apiclient | ^2.0 | ^2.18 |

## 2. Configuration

### Env var rename

| 1.x (lowercase) | 2.0 (uppercase) |
|---|---|
| `client_id` | `YOUTUBE_CLIENT_ID` |
| `client_secret` | `YOUTUBE_CLIENT_SECRET` |
| `api_key` | `YOUTUBE_API_KEY` |
| `redirect_url` | `YOUTUBE_REDIRECT_URL` |
| `app_name` | `YOUTUBE_APP_NAME` |

### Config file rename

`config/google-config.php` → `config/youtube.php`. Re-publish with `--force` and migrate any local edits.

```bash
php artisan vendor:publish --tag=youtube-config --force
```

## 3. Namespace casing

`alchemyguy\YoutubeLaravelApi\…` → `Alchemyguy\YoutubeLaravelApi\…`. Run a project-wide find/replace, then `composer dump-autoload`.

## 4. Method renames

| 1.x | 2.0 |
|---|---|
| `AuthenticateService::authChannelWithCode($code)` | `AuthenticateService::authenticateWithCode($code)` |
| `ChannelService::channelsListById($part, $params)` | `ChannelService::listById($params, $part)` |
| `ChannelService::getChannelDetails($token)` | `ChannelService::getOwnChannel($token)` |
| `ChannelService::subscriptionByChannelId($params, $part)` | `ChannelService::subscriptions($params, $part)` |
| `ChannelService::addSubscriptions($props, $token, $part, $params)` | `ChannelService::subscribe($token, $channelId)` |
| `ChannelService::removeSubscription($token, $id, $params)` | `ChannelService::unsubscribe($token, $subscriptionId)` |
| `ChannelService::updateChannelBrandingSettings($token, $props, $part, $params)` | `ChannelService::updateBranding($token, BrandingProperties)` |
| `VideoService::videosListById($part, $params)` | `VideoService::listById($params, $part)` |
| `VideoService::searchListByKeyword($part, $params)` | `VideoService::search($params, $part)` |
| `VideoService::relatedToVideoId(...)` | **REMOVED** |
| `VideoService::uploadVideo($token, $path, $data)` | `VideoService::upload($token, $path, VideoUploadData)` |
| `VideoService::deleteVideo($token, $id, $params)` | `VideoService::delete($token, $videoId)` |
| `VideoService::videosRate($token, $id, $rating, $params)` | `VideoService::rate($token, $videoId, Rating)` |
| `LiveStreamService::broadcast($token, $data)` | `LiveStreamService::broadcast($token, BroadcastData)` |
| `LiveStreamService::updateBroadcast($token, $data, $eventId)` | `LiveStreamService::updateBroadcast($token, $eventId, BroadcastData)` |
| `LiveStreamService::transitionEvent($token, $eventId, $status)` | `LiveStreamService::transition($token, $eventId, BroadcastStatus)` |
| `LiveStreamService::deleteEvent($token, $eventId)` | `LiveStreamService::delete($token, $eventId)` |

Several methods have **parameter-order changes** (most commonly `$params` moves before `$part` so the required arg comes first).

## 5. Removed methods

- `VideoService::relatedToVideoId()` — Google removed the `relatedToVideoId` parameter from `search.list` in [August 2023](https://developers.google.com/youtube/v3/revision_history#august-2023). The endpoint returns `400 Bad Request` for any request using it. There is no replacement.
- `AuthenticateService::deleteEvent()` — was an internal helper exposed by accident. Use `LiveStreamService::delete()`.

## 6. Behavior changes

### `broadcast()` no longer silently moves past start times to "now"

If `event_start_date_time` is in the past, an `\InvalidArgumentException` is thrown. Validate caller-side before invoking, or set the start time to `now()`.

### `liveStreamingEnabled` returns `bool`

Previously: `'enabled'` or `'disbaled'` (yes, the typo was real). Now: `true` / `false`.

### `liveStreamTest` no longer creates side effects

The 1.x implementation created a real broadcast and immediately deleted it as a capability probe (~50 quota units, polluted the channel). 2.0 uses a read-only `liveBroadcasts.list` call (1 quota unit, zero side effects).

### `getOwnChannel` returns `?array`

Previously threw on empty `items`. Now returns `null` if the user has no associated channel.

### Token refresh now persists

`OAuthService::setAccessToken()` returns the refreshed token (or `null` if no refresh occurred). The package also dispatches `Alchemyguy\YoutubeLaravelApi\Events\TokenRefreshed`. Listen for it to persist new tokens to your DB:

```php
Event::listen(function (TokenRefreshed $event) {
    Channel::where('youtube_token->access_token', $event->oldToken['access_token'])
        ->update(['youtube_token' => $event->newToken]);
});
```

## 7. Exception types

1.x threw bare `\Exception` for everything. 2.0 throws typed exceptions:

```
YoutubeException (extends \RuntimeException)
├── ConfigurationException
├── AuthenticationException
├── LiveStreamingNotEnabledException
└── YoutubeApiException
    └── QuotaExceededException
```

Catching `\Exception` still works. You can now also catch specific types.

## 8. Dependency injection (optional but recommended)

```php
// 1.x style — still works
$live = new LiveStreamService();

// 2.0 preferred — use the container
public function __construct(private LiveStreamService $live) {}

// 2.0 with custom client (tests, multi-account)
$live = LiveStreamService::withClient($customClient);
```

## 9. Removed dev tooling

- `squizlabs/php_codesniffer` is no longer a dev dependency. The `composer check-style` and `composer fix-style` scripts are replaced by `composer lint` and `composer fix` (Laravel Pint).
- `.styleci.yml` was removed (StyleCI shut down years ago).
