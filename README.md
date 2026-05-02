# YoutubeLaravelApi

[![Tests](https://github.com/alchemyguy/YoutubeLaravelApi/actions/workflows/tests.yml/badge.svg)](https://github.com/alchemyguy/YoutubeLaravelApi/actions/workflows/tests.yml)
[![Static analysis](https://github.com/alchemyguy/YoutubeLaravelApi/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/alchemyguy/YoutubeLaravelApi/actions/workflows/static-analysis.yml)
[![Latest version](https://img.shields.io/packagist/v/alchemyguy/youtube-laravel-api.svg)](https://packagist.org/packages/alchemyguy/youtube-laravel-api)
[![License](https://img.shields.io/packagist/l/alchemyguy/youtube-laravel-api.svg)](LICENSE)

Modern Laravel wrapper for the YouTube Data API v3 with OAuth, live broadcast control, channel management, and resumable video uploads.

**Documentation:** https://alchemyguy.github.io/YoutubeLaravelApi/

## Requirements

- PHP 8.3 or higher
- Laravel 11.x or 12.x

## Install

```bash
composer require alchemyguy/youtube-laravel-api
php artisan vendor:publish --tag=youtube-config
```

Add credentials to your `.env`:

```dotenv
YOUTUBE_APP_NAME="My App"
YOUTUBE_CLIENT_ID="your-client-id.apps.googleusercontent.com"
YOUTUBE_CLIENT_SECRET="your-client-secret"
YOUTUBE_API_KEY="your-server-api-key"
YOUTUBE_REDIRECT_URL="https://yourapp.test/oauth/youtube/callback"
```

## Quick example

```php
use Alchemyguy\YoutubeLaravelApi\Services\AuthenticateService;
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\LiveStreamService;
use Alchemyguy\YoutubeLaravelApi\DTOs\BroadcastData;

// 1. OAuth
$auth = app(AuthenticateService::class);
$url = $auth->getLoginUrl('creator@example.com', 'channel-id');
// ... redirect, handle callback ...
$result = $auth->authenticateWithCode($code);

// 2. Schedule a live broadcast
$live = app(LiveStreamService::class);
$broadcast = $live->broadcast($result['token'], new BroadcastData(
    title: 'My Stream',
    description: 'Live coding',
    scheduledStartTime: new DateTimeImmutable('+10 minutes'),
));
```

## Documentation

The full guide, API reference, and examples live at **[alchemyguy.github.io/YoutubeLaravelApi](https://alchemyguy.github.io/YoutubeLaravelApi/)**.

- [Installation](https://alchemyguy.github.io/YoutubeLaravelApi/guide/installation)
- [Authentication](https://alchemyguy.github.io/YoutubeLaravelApi/guide/authentication)
- [Live streaming](https://alchemyguy.github.io/YoutubeLaravelApi/guide/live-streaming)
- [Channels](https://alchemyguy.github.io/YoutubeLaravelApi/guide/channels)
- [Videos](https://alchemyguy.github.io/YoutubeLaravelApi/guide/videos)
- [Error handling](https://alchemyguy.github.io/YoutubeLaravelApi/guide/error-handling)
- [Upgrading from 1.x](https://alchemyguy.github.io/YoutubeLaravelApi/upgrading/from-1.x)

## Upgrading from 1.x

If you're on the previous version, see [UPGRADE.md](UPGRADE.md) — there are several breaking changes.

## Contributing

Contributions welcome — see [CONTRIBUTING.md](CONTRIBUTING.md). Please run `composer lint` and `composer test:unit` before opening a PR.

## Security

If you discover a security vulnerability, please email the maintainer directly rather than opening an issue.

## License

The MIT License (MIT). See [LICENSE](LICENSE) for details.

## Credits

- Created by [Mukesh Chandra](https://github.com/alchemyguy)
- 2.0 modernization by the contributors
