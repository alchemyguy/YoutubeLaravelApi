# Testing your code

The package is built for easy mocking. Every service exposes a `withClient(Google\Client $client)` static factory so you can inject your own (mocked) client in tests.

## Mocking with Mockery

```php
use Alchemyguy\YoutubeLaravelApi\Services\VideoService;
use Google\Client;
use Google\Service\YouTube;
use Google\Service\YouTube\Resource\Videos;
use Mockery;

it('uploads to youtube', function () {
    $videosResource = Mockery::mock(Videos::class);
    $videosResource->shouldReceive('insert')->once()->andReturn(['id' => 'fake-id']);

    $youtube = Mockery::mock(YouTube::class);
    $youtube->videos = $videosResource;

    // Bind your mock client to the service
    $service = VideoService::withClient(Mockery::mock(Client::class));

    // ... use a partial mock or test double to inject $youtube
});
```

## Using PHPUnit assertions

The `BaseService` exposes `client()` and `oauth()` so you can verify behavior:

```php
$service = VideoService::withClient($client);
$this->assertSame($client, $service->client());
```

## Testing token refresh

```php
use Alchemyguy\YoutubeLaravelApi\Events\TokenRefreshed;
use Illuminate\Support\Facades\Event;

Event::fake();
// ... action that triggers refresh ...
Event::assertDispatched(TokenRefreshed::class);
```
