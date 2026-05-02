# Live streaming

## Create a broadcast

```php
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\LiveStreamService;
use Alchemyguy\YoutubeLaravelApi\DTOs\BroadcastData;
use Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus;

$live = app(LiveStreamService::class);

$result = $live->broadcast($token, new BroadcastData(
    title: 'My Stream',
    description: 'Live coding session',
    scheduledStartTime: new DateTimeImmutable('+10 minutes'),
    privacyStatus: PrivacyStatus::Public,
    languageName: 'English',
    tags: ['coding', 'laravel'],
));

$broadcastId = $result['broadcast']['id'];
$rtmpUrl     = $result['stream']['cdn']['ingestionInfo']['ingestionAddress'];
$streamKey   = $result['stream']['cdn']['ingestionInfo']['streamName'];
```

Pass `$rtmpUrl` and `$streamKey` to your encoder (OBS, ffmpeg, etc.) to start sending video.

## State transitions

```php
use Alchemyguy\YoutubeLaravelApi\Enums\BroadcastStatus;

// Verify the encoder is sending data
$live->transition($token, $broadcastId, BroadcastStatus::Testing);

// Go live
$live->transition($token, $broadcastId, BroadcastStatus::Live);

// End the broadcast
$live->transition($token, $broadcastId, BroadcastStatus::Complete);
```

## Update a broadcast

```php
$live->updateBroadcast($token, $broadcastId, new BroadcastData(
    title: 'Updated title',
    description: 'Updated description',
    scheduledStartTime: new DateTimeImmutable('+15 minutes'),
));
```

Note that updating produces a **new** RTMP stream (with a new key) — your encoder must reconnect.

## Delete a broadcast

```php
$live->delete($token, $broadcastId);
```

## Backward-compatible array data

If you have existing 1.x-style array-based callers, use `BroadcastData::fromArray()`:

```php
$result = $live->broadcast($token, BroadcastData::fromArray([
    'title' => 'X',
    'description' => 'Y',
    'event_start_date_time' => '2026-05-15 14:00:00',
    'time_zone' => 'America/Los_Angeles',
    'privacy_status' => 'public',
    'language_name' => 'English',
    'tag_array' => ['gaming', 'live'],
]));
```
