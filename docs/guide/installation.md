# Installation

## Requirements

| Component | Version |
|---|---|
| PHP | 8.3 or higher |
| Laravel | 11.x or 12.x |
| google/apiclient | ^2.18 (auto) |

## Install

```bash
composer require alchemyguy/youtube-laravel-api
```

The package is auto-discovered via Laravel's package discovery — no manual provider registration needed.

## Publish the config

```bash
php artisan vendor:publish --tag=youtube-config
```

This creates `config/youtube.php` in your application.

## Set environment variables

Add the following to your `.env`:

```dotenv
YOUTUBE_APP_NAME="My App"
YOUTUBE_CLIENT_ID="your-client-id.apps.googleusercontent.com"
YOUTUBE_CLIENT_SECRET="your-client-secret"
YOUTUBE_API_KEY="your-server-api-key"
YOUTUBE_REDIRECT_URL="https://yourapp.test/oauth/youtube/callback"
```

## Next steps

→ [Configuration](/guide/configuration)
→ [Authentication](/guide/authentication)
