# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [2.0.0] - 2026-05-XX

### BREAKING CHANGES
- Bumped minimum PHP to 8.3 and minimum Laravel to 11.x.
- Renamed root namespace from `alchemyguy\YoutubeLaravelApi\` to `Alchemyguy\YoutubeLaravelApi\` (StudlyCase vendor).
- Renamed config file from `google-config.php` to `youtube.php`.
- Renamed env vars to uppercase `YOUTUBE_*` (e.g. `client_id` → `YOUTUBE_CLIENT_ID`).
- Many service methods renamed; see UPGRADE.md for the full table.
- Several methods now take typed DTOs / enums instead of arrays / strings (`BroadcastData`, `VideoUploadData`, `BrandingProperties`, `BroadcastStatus`, `Rating`, `PrivacyStatus`).
- Service methods now throw typed `YoutubeException` subclasses instead of bare `\Exception`.
- `broadcast()` and `updateBroadcast()` now throw `\InvalidArgumentException` for past start times instead of silently using "now".
- `liveStreamingEnabled` is a `bool` (was `'enabled'`/`'disbaled'` string).
- Removed `VideoService::relatedToVideoId()` — Google removed the underlying parameter in August 2023.
- Removed `AuthenticateService::deleteEvent()` — internal helper accidentally exposed.

### Added
- `YoutubeClientFactory` for clean DI of `Google\Client`.
- `withClient()` factory on every service for test injection.
- `TokenRefreshed` event dispatched on token refresh.
- `OAuthService::setAccessToken()` returns the refreshed token (or null).
- Pest-based test suite with mocked Google client.
- GitHub Actions CI: PHP 8.3/8.4 × Laravel 11/12 matrix; PHPStan level 8; Pint.
- Hosted documentation site at `https://alchemyguy.github.io/YoutubeLaravelApi/`.
- Read-only live-streaming capability probe (1 quota unit vs. 1.x's ~50).

### Fixed
- Inverted token guard in `transitionEvent` (1.x bug — function was non-functional).
- `videosRate` referenced undefined `$client` variable (1.x bug — function was broken).
- Config key mismatch caused language mapping to silently fall back to "en" (1.x bug).
- `count($data["tag_array"])` blew up if `tag_array` was unset.
- Hardcoded `image/png` MIME on thumbnail uploads — now detected.
- `setDefer(false)` now runs in `try/finally`, preventing client corruption after upload errors.
- `getOwnChannel` returns `?array` instead of throwing on empty items.
- `subscriptionByChannelId` no longer constructs an unused `Google_Service_YouTube` instance per call.
- Two duplicate `deleteEvent` methods on different classes consolidated to one.

### Changed
- All exception handling consolidated via `BaseService::call()` wrapping `Google\Service\Exception` into typed `YoutubeApiException` subclasses.
- `LiveStreamService` split into orchestrator + `BroadcastManager`, `StreamManager`, `ThumbnailUploader`.

### Removed
- `squizlabs/php_codesniffer` (replaced by Laravel Pint).
- `.styleci.yml` (StyleCI shut down).

[Unreleased]: https://github.com/alchemyguy/YoutubeLaravelApi/compare/v2.0.0...HEAD
[2.0.0]: https://github.com/alchemyguy/YoutubeLaravelApi/releases/tag/v2.0.0
