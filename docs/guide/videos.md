# Videos

## Public lookup

```php
use Alchemyguy\YoutubeLaravelApi\Services\VideoService;

$videos = app(VideoService::class);

$result = $videos->listById(['id' => 'dQw4w9WgXcQ']);
$result = $videos->listById(['id' => 'a,b,c'], 'snippet,statistics');
```

## Search

```php
$result = $videos->search([
    'q' => 'laravel tutorial',
    'maxResults' => 25,
    'type' => 'video',
]);
```

::: warning
The `relatedToVideoId` parameter was [removed by Google in August 2023](https://developers.google.com/youtube/v3/revision_history#august-2023). Related-video discovery is no longer supported.
:::

## Upload a video

```php
use Alchemyguy\YoutubeLaravelApi\DTOs\VideoUploadData;
use Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus;

$result = $videos->upload($token, '/path/to/video.mp4', new VideoUploadData(
    title: 'My Video',
    description: 'Description',
    categoryId: '22',  // see https://developers.google.com/youtube/v3/docs/videoCategories/list
    privacyStatus: PrivacyStatus::Unlisted,
    tags: ['laravel', 'php'],
));
```

The upload is resumable and chunked (default 1 MiB chunks). For large files on slow connections, increase chunks:

```php
new VideoUploadData(
    /* ... */
    chunkSizeBytes: 4 * 1024 * 1024,  // 4 MiB
);
```

Maximum video size: 128 GB or 12 hours, whichever comes first.

## Delete

```php
$videos->delete($token, 'video-id');
```

## Rate

```php
use Alchemyguy\YoutubeLaravelApi\Enums\Rating;

$videos->rate($token, 'video-id', Rating::Like);
$videos->rate($token, 'video-id', Rating::Dislike);
$videos->rate($token, 'video-id', Rating::None);  // remove rating
```
