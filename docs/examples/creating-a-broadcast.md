# Example: Creating a broadcast end-to-end

This example walks through scheduling a broadcast, starting the stream, and ending it.

## 1. Schedule

```php
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\LiveStreamService;
use Alchemyguy\YoutubeLaravelApi\DTOs\BroadcastData;
use Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus;

$live = app(LiveStreamService::class);

$result = $live->broadcast($channel->youtube_token, new BroadcastData(
    title: 'Live: Building a YouTube CLI in Rust',
    description: '2-hour streaming session, building a CLI from scratch.',
    scheduledStartTime: new DateTimeImmutable('+15 minutes'),
    scheduledEndTime: new DateTimeImmutable('+135 minutes'),
    privacyStatus: PrivacyStatus::Public,
    languageName: 'English',
    thumbnailPath: storage_path('app/thumbnails/coding.png'),
    tags: ['rust', 'cli', 'live coding'],
));

$broadcast = Broadcast::create([
    'channel_id'  => $channel->id,
    'youtube_id'  => $result['broadcast']['id'],
    'rtmp_url'    => $result['stream']['cdn']['ingestionInfo']['ingestionAddress'],
    'stream_key'  => $result['stream']['cdn']['ingestionInfo']['streamName'],
]);
```

## 2. Test before going live

```php
use Alchemyguy\YoutubeLaravelApi\Enums\BroadcastStatus;

// Encoder is now sending video to RTMP server.
// Confirm YouTube is receiving and the stream is healthy.
$live->transition($channel->youtube_token, $broadcast->youtube_id, BroadcastStatus::Testing);
```

## 3. Go live

```php
$live->transition($channel->youtube_token, $broadcast->youtube_id, BroadcastStatus::Live);
$broadcast->update(['went_live_at' => now()]);
```

## 4. End the broadcast

```php
$live->transition($channel->youtube_token, $broadcast->youtube_id, BroadcastStatus::Complete);
$broadcast->update(['ended_at' => now()]);
```
