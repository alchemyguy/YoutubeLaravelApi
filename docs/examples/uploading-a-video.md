# Example: Uploading a video with progress tracking

```php
use Alchemyguy\YoutubeLaravelApi\Services\VideoService;
use Alchemyguy\YoutubeLaravelApi\DTOs\VideoUploadData;
use Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus;

class UploadVideoJob implements ShouldQueue
{
    public function handle(VideoService $videos): void
    {
        $data = new VideoUploadData(
            title:        $this->video->title,
            description:  $this->video->description,
            categoryId:   '22',
            privacyStatus: PrivacyStatus::Unlisted,
            tags:         $this->video->tags->pluck('name')->all(),
            chunkSizeBytes: 4 * 1024 * 1024,
        );

        try {
            $result = $videos->upload($this->channel->youtube_token, $this->video->path, $data);
            $this->video->update([
                'youtube_id' => $result['id'] ?? null,
                'uploaded_at' => now(),
            ]);
        } catch (\Alchemyguy\YoutubeLaravelApi\Exceptions\QuotaExceededException $e) {
            $this->release(60 * 60 * 24);  // try again tomorrow
        }
    }
}
```
