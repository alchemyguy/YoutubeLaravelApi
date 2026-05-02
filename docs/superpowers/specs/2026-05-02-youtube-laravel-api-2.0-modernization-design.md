# YoutubeLaravelApi 2.0 Modernization — Design Spec

**Status:** Approved (brainstorming complete)
**Date:** 2026-05-02
**Author:** Mukesh Chandra (with Claude)
**Target release:** v2.0.0
**Branch:** `feat/2.0-modernization`

---

## 1. Goals & non-goals

### Goals
- Bring the package onto modern PHP (8.3+) and Laravel (11+).
- Upgrade `google/apiclient` to `^2.18` and use the namespaced `Google\…` class names instead of the deprecated `Google_…` aliases.
- Fix every known bug in the 1.x codebase (17 issues catalogued in §6).
- Replace the `\Config::` facade coupling with proper dependency injection.
- Add a real test suite (unit + opt-in integration), CI, static analysis, and code style enforcement.
- Provide a hosted documentation site.
- Ship as a clean 2.0 release with a clear UPGRADE guide.

### Non-goals
- Backward compatibility with 1.x at the source level. Anyone on 1.x migrates explicitly via UPGRADE.md.
- Supporting PHP < 8.3 or Laravel < 11.
- Adding YouTube features beyond what 1.x exposed (Comments API, Analytics API, Reporting API, Captions, Playlists). Those can live in a 2.x minor.
- Multi-tenant credential management as a first-class feature (the `withClient()` builder is sufficient for now).

## 2. Target version matrix

| Component | Floor | Ceiling tested |
|---|---|---|
| PHP | 8.3 | 8.4 |
| Laravel | 11.x | 12.x |
| google/apiclient | ^2.18 | latest 2.x |

Rationale: PHP 8.3 unlocks readonly anonymous classes, typed class constants, and `json_validate()`. PHP 8.2 was considered but excluded per Section 1 of brainstorming (aggressive floor chosen). Laravel 10 was excluded for the same reason — Laravel 11+ supports PHP 8.2+ as floor, which fits our PHP target.

## 3. High-level architecture

### Public API shape
Consumers can use the package three ways:

```php
// 1. Direct instantiation (1.x-compatible style; container resolves under the hood)
$live = new LiveStreamService();

// 2. Container resolution (preferred; idiomatic Laravel)
public function __construct(private LiveStreamService $live) {}

// 3. Custom client injection (tests, multi-account)
$live = LiveStreamService::withClient($mockedClient);
```

### Dependency flow

```
config/youtube.php
        │
        ▼
YoutubeClientFactory ──── builds ────▶ Google\Client
        │                                   │
        │                                   │ injected into
        ▼                                   ▼
YoutubeLaravelApiServiceProvider     [Service classes]
   binds singletons                  AuthenticateService
        │                            ChannelService
        ▼                            VideoService
   Container                         LiveStreamService
                                          │
                                          ├─ BroadcastManager
                                          ├─ StreamManager
                                          └─ ThumbnailUploader
```

Each service accepts an optional `Google\Client` in its constructor; if omitted it resolves the factory and calls `make()`. `withClient()` is a static named constructor for tests.

### Namespace
Root namespace changes from `alchemyguy\YoutubeLaravelApi` (lowercase vendor) to `Alchemyguy\YoutubeLaravelApi` (proper StudlyCase). Composer package name (`alchemyguy/youtube-laravel-api`) is unchanged.

## 4. Module breakdown

### 4.1 Directory layout
```
src/
├── YoutubeLaravelApiServiceProvider.php
├── Support/
│   ├── YoutubeClientFactory.php
│   ├── ResourceBuilder.php
│   └── DurationParser.php
├── Auth/
│   └── OAuthService.php
├── Services/
│   ├── AuthenticateService.php
│   ├── ChannelService.php
│   ├── VideoService.php
│   └── LiveStream/
│       ├── LiveStreamService.php
│       ├── BroadcastManager.php
│       ├── StreamManager.php
│       └── ThumbnailUploader.php
├── DTOs/
│   ├── BroadcastData.php
│   ├── VideoUploadData.php
│   └── BrandingProperties.php
├── Enums/
│   ├── BroadcastStatus.php
│   ├── Rating.php
│   └── PrivacyStatus.php
├── Events/
│   └── TokenRefreshed.php
├── Exceptions/
│   ├── YoutubeException.php
│   ├── ConfigurationException.php
│   ├── AuthenticationException.php
│   ├── LiveStreamingNotEnabledException.php
│   ├── QuotaExceededException.php
│   └── YoutubeApiException.php
└── config/youtube.php
```

### 4.2 Why `LiveStreamService` was split
The 1.x `LiveStreamService::broadcast()` does five sequential things in 90 lines:
1. Insert `liveBroadcast`
2. Optionally upload thumbnail
3. Read video back, update tags + language
4. Insert `liveStream` (CDN format, ingestion type)
5. Bind broadcast to stream

Splitting into `BroadcastManager`, `StreamManager`, `ThumbnailUploader` makes each step independently testable and lets `LiveStreamService::broadcast()` become a thin orchestrator (~30 lines). Each manager has one clear purpose and a small surface — easy to reason about, easy to mock.

### 4.3 Public API method names
Comprehensive rename table (1.x → 2.0). Note that some methods also have **parameter-order changes** (most commonly `$params` moves before `$part` so the required arg comes first):

| 1.x | 2.0 |
|---|---|
| `AuthenticateService::authChannelWithCode($code)` | `AuthenticateService::authenticateWithCode($code)` |
| `ChannelService::channelsListById($part, $params)` | `ChannelService::listById($params, $part)` |
| `ChannelService::getChannelDetails($token)` | `ChannelService::getOwnChannel($token)` |
| `ChannelService::subscriptionByChannelId($params, $part)` | `ChannelService::subscriptions($params, $part)` |
| `ChannelService::addSubscriptions($props, $token, $part, $params)` | `ChannelService::subscribe($token, $channelId)` |
| `ChannelService::removeSubscription($token, $id, $params)` | `ChannelService::unsubscribe($token, $subscriptionId)` |
| `ChannelService::updateChannelBrandingSettings($token, $props, $part, $params)` | `ChannelService::updateBranding($token, $properties)` |
| `VideoService::videosListById($part, $params)` | `VideoService::listById($params, $part)` |
| `VideoService::searchListByKeyword($part, $params)` | `VideoService::search($params, $part)` |
| `VideoService::relatedToVideoId(...)` | **REMOVED** (Google killed the parameter Aug 2023) |
| `VideoService::uploadVideo($token, $path, $data)` | `VideoService::upload($token, $path, VideoUploadData)` |
| `VideoService::deleteVideo($token, $id, $params)` | `VideoService::delete($token, $videoId)` |
| `VideoService::videosRate($token, $id, $rating, $params)` | `VideoService::rate($token, $videoId, Rating)` |
| `LiveStreamService::broadcast($token, $data)` | `LiveStreamService::broadcast($token, BroadcastData)` |
| `LiveStreamService::updateBroadcast($token, $data, $eventId)` | `LiveStreamService::updateBroadcast($token, $eventId, BroadcastData)` |
| `LiveStreamService::transitionEvent($token, $eventId, $status)` | `LiveStreamService::transition($token, $eventId, BroadcastStatus)` |
| `LiveStreamService::deleteEvent($token, $eventId)` | `LiveStreamService::delete($token, $eventId)` |

### 4.4 New value objects

**Backed enums:**
```php
enum BroadcastStatus: string {
    case Testing = 'testing';
    case Live = 'live';
    case Complete = 'complete';
}

enum Rating: string {
    case Like = 'like';
    case Dislike = 'dislike';
    case None = 'none';
}

enum PrivacyStatus: string {
    case Public = 'public';
    case Private = 'private';
    case Unlisted = 'unlisted';
}
```

**Readonly DTOs:**
```php
final readonly class BroadcastData {
    public function __construct(
        public string $title,
        public string $description,
        public \DateTimeImmutable $scheduledStartTime,
        public ?\DateTimeImmutable $scheduledEndTime = null,
        public PrivacyStatus $privacyStatus = PrivacyStatus::Public,
        public string $languageName = 'English',
        public ?string $thumbnailPath = null,
        public array $tags = [],
    ) {
        // validate scheduledStartTime not in past
        // validate tag length sum < 500 chars
    }

    public static function fromArray(array $data): self { /* … */ }
}
```

DTO validation runs in the constructor — no more silent fallbacks. `BroadcastData::fromArray()` provides a migration path from 1.x array-based callers.

### 4.5 Configuration

`config/youtube.php` (renamed from `google-config.php`):
```php
return [
    'app_name'     => env('YOUTUBE_APP_NAME'),
    'client_id'    => env('YOUTUBE_CLIENT_ID'),
    'client_secret'=> env('YOUTUBE_CLIENT_SECRET'),
    'api_key'      => env('YOUTUBE_API_KEY'),
    'redirect_url' => env('YOUTUBE_REDIRECT_URL'),
    'languages'    => [/* same map as 1.x */],
];
```

`YoutubeClientFactory::make()` validates that `client_id`, `client_secret`, and `redirect_url` are non-empty; throws `ConfigurationException` if not. (1.x silently constructed a half-broken client.)

## 5. Removed / replaced functionality

### Removed
- **`VideoService::relatedToVideoId()`** — The `relatedToVideoId` parameter on `search.list` was removed by Google in August 2023. The endpoint returns a `400 Bad Request` for any request using it. There is no replacement.
- **`AuthenticateService::deleteEvent()`** — Was an internal helper accidentally exposed. Callers should use `LiveStreamService::delete()`.

### Replaced
- **`AuthenticateService::liveStreamTest()`** — Previously created a real broadcast and immediately deleted it (~50 quota units, side effects on the channel). Replaced with a read-only `liveBroadcasts.list({mine: true, maxResults: 1})` call (1 quota unit, zero side effects). Detects "live streaming not enabled" via Google's specific error code (`liveStreamingNotEnabled`).

### Kept (with documentation)
- **`subscribe()` / `unsubscribe()`** — YouTube heavily rate-limits subscription writes (anti-bot) but the endpoints still work for legitimate first-party use. Docblock notes the restrictions.
- **`liveBroadcasts.bind` flow in `broadcast()`** — Google still supports the manual broadcast → stream binding. The unified live-streaming API (auto-bind via `contentDetails.boundStreamId` on insert) is an alternative for a future 2.x minor, not required for 2.0.

## 6. Bug fixes

Each fix gets a corresponding regression test in §8.

| # | Source location | Bug | Resolution |
|---|---|---|---|
| 1 | `LiveStreamService.php:306-308` | `transitionEvent` has inverted guard `if (!empty($token)) return false;` — non-functional. | Replace with `if (empty($token)) throw new ConfigurationException(...)`. |
| 2 | `VideoService.php:248` | `videosRate` calls `new Google_Service_YouTube($client)` — missing `\` prefix and `$client` undefined; never returns response. | Use `new Google\Service\YouTube($this->client)`, return the response. |
| 3 | `LiveStreamService.php:282` | `updateTags` references undefined `$data["tag_array"]` instead of `$tagsArray`. | Method absorbed into `BroadcastManager::updateMetadata`; fixed inline. |
| 4 | `Auth/AuthService.php:28` | Reads `\Config::get('google.yt_language')` — wrong key. `$this->ytLanguage` always null; language mapping always falls back to `"en"`. | Read `youtube.languages`. Language mapping starts working. |
| 5 | `LiveStreamService.php:68` | `count($data["tag_array"]) > 0` blows up if unset; warns on non-arrays in PHP 7.2+. | Replaced by typed `BroadcastData` DTO with `array $tags = []` default. |
| 6 | `LiveStreamService.php:225` | `uploadThumbnail` hardcodes `image/png`. | Detect mime via `mime_content_type()`; validate against YouTube's allowed types (`image/jpeg`, `image/png`). |
| 7 | `LiveStreamService.php:248`, `VideoService.php:185` | `setDefer(false)` only runs on success path. Exceptions mid-upload leave client in deferred mode. | Wrap upload in `try/finally`. |
| 8 | `AuthenticateService.php:33` | Typo: `'disbaled'`. | Replaced with `bool` return. |
| 9 | `ChannelService.php:85` | `updateChannelBrandingSettings` requires 4 args; README documents 2. | New `updateBranding($token, $properties)` matches docs. |
| 10 | `ChannelService.php:117` | Constructs unused `Google_Service_YouTube`; ignores `$part`. | Single instance reused; `$part` actually applied. |
| 11 | Two `deleteEvent` methods | Different signatures on `AuthenticateService` and `LiveStreamService` — easy to call the wrong one. | Single `BroadcastManager::delete()`; `AuthenticateService::deleteEvent` removed. |
| 12 | All services | Triplicate `catch` blocks rethrow as bare `\Exception` — loses type info. | `ExceptionHandler::wrap(callable)` helper maps Google exceptions to typed `YoutubeException` subclasses, preserving `previous` chain. |
| 13 | `LiveStreamService.php:64` | `Carbon::createFromFormat` returns `false` on bad input — silent failure. | DTO constructor validates; throws `\InvalidArgumentException`. |
| 14 | `LiveStreamService.php:65, 373, 382` | "If start time is in past, use now" silently masks user error. | Throw `\InvalidArgumentException`. **Documented breaking behavior.** |
| 15 | `LiveStreamService.php:117` | Unchecked `$listResponse[0]` access; opaque error on eventual-consistency miss. | Guard with explicit check + retry (max 3, 500ms backoff). |
| 16 | `ChannelService.php:54` | `getChannelDetails` returns `$response['items'][0]` unchecked; throws on empty. | Return `?array`; document. |
| 17 | `Auth/AuthService.php:104` | Token refresh assigns to `$newToken` but never persists; caller has no way to know. | Return refreshed token from `setAccessToken`; dispatch `TokenRefreshed` event. |

### Quota impact
The replaced `liveStreamTest()` reduces capability detection from ~50 units (insert + delete) to 1 unit (list). On the default 10,000-unit/day quota, this matters for high-traffic users.

## 7. Exceptions

```
YoutubeException (extends \RuntimeException) [base]
├── ConfigurationException        # missing/invalid config
├── AuthenticationException       # token exchange / refresh failure
├── LiveStreamingNotEnabledException
├── QuotaExceededException        # API quota hit
└── YoutubeApiException           # wraps Google\Service\Exception
       └─ preserves getCode() and getErrors()
```

All package-thrown exceptions inherit from `\RuntimeException`, so existing 1.x `catch (\Exception $e)` blocks continue to work. Users gain the option of catching specific types.

Note: DTO constructors throw stdlib `\InvalidArgumentException` for invalid input (idiomatic for value-object validation). This is intentionally *outside* the `YoutubeException` hierarchy — invalid input is a programming error, not a runtime API failure.

## 8. Testing

### Layout
```
tests/
├── Pest.php
├── TestCase.php                  # extends Orchestra\Testbench\TestCase
├── Unit/
│   ├── Support/
│   │   ├── ResourceBuilderTest.php
│   │   ├── DurationParserTest.php
│   │   └── YoutubeClientFactoryTest.php
│   ├── Auth/OAuthServiceTest.php
│   ├── AuthenticateServiceTest.php
│   ├── ChannelServiceTest.php
│   ├── VideoServiceTest.php
│   └── LiveStream/
│       ├── BroadcastManagerTest.php
│       ├── StreamManagerTest.php
│       ├── ThumbnailUploaderTest.php
│       └── LiveStreamServiceTest.php
├── Integration/                  # @group integration, opt-in
│   ├── IntegrationTestCase.php
│   ├── AuthenticationFlowTest.php
│   ├── LiveBroadcastFlowTest.php
│   └── VideoUploadFlowTest.php
└── Fixtures/
    ├── youtube_responses/        # captured JSON, used in mocks
    └── images/test_thumbnail.png
```

### Framework
Pest 3 on PHPUnit 11. Mockery for Google client mocks.

### Mocking strategy
Each unit test injects a Mockery-mocked `Google\Client` via `Service::withClient($mock)`. Mock returns canned responses loaded from `tests/Fixtures/youtube_responses/*.json` — real responses captured once from the live API and committed.

### Coverage targets
- 100% line coverage on `src/` excluding `YoutubeClientFactory::make()` (calls real Google SDK constructors).
- Each of the 17 fixed bugs gets a regression test.

### Integration test gate
`IntegrationTestCase::setUp()` calls `markTestSkipped()` if `YOUTUBE_INTEGRATION_TEST_TOKEN` and `YOUTUBE_INTEGRATION_TEST_CHANNEL_ID` env vars are missing. Local-only; never run in CI.

### Cleanup discipline
Integration tests register every YouTube resource ID created in a `tearDown()` cleanup queue. A `tests/Integration/cleanup.php` script handles orphans from interrupted runs.

## 9. Tooling

### Code style
**Laravel Pint** (drops PHP_CodeSniffer). `composer fix` runs it; `composer lint` is `pint --test`.

### Static analysis
**PHPStan level 8** with `larastan/larastan`. `composer analyse` runs it. Level 8 forces actual handling of nullable returns.

### Modernization
**Rector** with PHP 8.3 + Laravel rule sets. Used for the migration; not a CI gate.

### Dependencies removed
- `squizlabs/php_codesniffer` — replaced by Pint.
- `.styleci.yml` — StyleCI is dead.

## 10. CI (GitHub Actions)

```
.github/workflows/
├── tests.yml          # matrix: PHP 8.3/8.4 × Laravel 11/12 × prefer-lowest/stable
├── static-analysis.yml # Pint --test + PHPStan
├── docs.yml           # build & deploy VitePress to gh-pages
└── release.yml        # on tag v*: GitHub release notes from CHANGELOG
```

Branch protection on `master`: tests + static-analysis required, 1 approval, no force-push, linear history.

## 11. Documentation site

### Stack
**VitePress** on **GitHub Pages**, hosted at `https://alchemyguy.github.io/YoutubeLaravelApi/`.

Why VitePress: lighter than Docusaurus (no React/MDX requirement), better DX than MkDocs, modern visual polish, Markdown-first. Used by Vue, Vite, Pinia, multiple Laravel-ecosystem packages.

### Layout
```
docs/
├── .vitepress/config.ts
├── public/                       # logo, favicon, OG image
├── index.md                      # landing page
├── guide/
│   ├── installation.md
│   ├── configuration.md
│   ├── authentication.md
│   ├── live-streaming.md
│   ├── channels.md
│   ├── videos.md
│   ├── error-handling.md
│   └── testing.md
├── api/                          # generated from docblocks
│   ├── auth.md
│   ├── channel-service.md
│   ├── live-stream-service.md
│   └── video-service.md
├── upgrading/from-1.x.md         # mirrors UPGRADE.md
└── examples/
    ├── creating-a-broadcast.md
    ├── uploading-a-video.md
    └── multi-account.md
```

### API reference generation
`phpDocumentor` generates raw API docs from docblocks; a Node script in `docs.yml` converts to VitePress Markdown in `docs/api/`. Auto-regenerated on every push to `master`.

## 12. Composer scripts

```json
"scripts": {
    "test":             "pest",
    "test:unit":        "pest --testsuite=Unit",
    "test:integration": "pest --group=integration",
    "test:coverage":    "pest --coverage --min=95",
    "analyse":          "phpstan analyse",
    "fix":              "pint",
    "lint":             "pint --test",
    "rector":           "rector process --dry-run",
    "docs:dev":         "npm --prefix docs run dev",
    "docs:build":       "npm --prefix docs run build"
}
```

## 13. Branch & release workflow

1. Feature branch `feat/2.0-modernization` cut from `master`. Even this spec lands via PR.
2. Sub-branches off feature branch for each implementation phase if scope warrants; merged into the feature branch via PR.
3. Final PR `feat/2.0-modernization → master` is the release PR.
4. Tag `v2.0.0-beta.1` from `master` after merge → 2-week feedback window → `v2.0.0`.
5. Release notes auto-generated from `CHANGELOG.md` via `release.yml`.
6. `1.x` branch kept alive for security backports.

## 14. Files added / removed at repo level

### Added
- `phpunit.xml.dist`
- `phpstan.neon`
- `pint.json`
- `rector.php`
- `.github/workflows/{tests,static-analysis,docs,release}.yml`
- `.github/dependabot.yml`
- `.github/ISSUE_TEMPLATE/{bug_report,feature_request}.yml`
- `.github/pull_request_template.md`
- `CHANGELOG.md` (Keep a Changelog format)
- `UPGRADE.md`
- `docs/` (VitePress site)
- All new source files under `src/` per §4.1

### Removed
- `.styleci.yml`
- `ISSUE_TEMPLATE.md` (root level → `.github/ISSUE_TEMPLATE/*.yml`)
- `PULL_REQUEST_TEMPLATE.md` (root level → `.github/pull_request_template.md`)
- `src/Auth/AuthService.php` (renamed/split — see §4.1)
- `src/AuthenticateService.php` (moved into `src/Services/`)
- `src/ChannelService.php` (moved into `src/Services/`)
- `src/LiveStreamService.php` (moved & split into `src/Services/LiveStream/`)
- `src/VideoService.php` (moved into `src/Services/`)
- `src/config/google-config.php` (renamed to `src/config/youtube.php`)

## 15. UPGRADE.md outline

The full UPGRADE.md will cover:

1. **TL;DR** — composer bump, PHP/Laravel floor, env rename, namespace rename
2. **Requirements** matrix
3. **Configuration** — env var rename, config file rename
4. **Namespace casing** — `alchemyguy\` → `Alchemyguy\`
5. **Method renames** — full table from §4.3
6. **Removed methods** — `relatedToVideoId`, `AuthenticateService::deleteEvent`
7. **Behavior changes:**
   - `broadcast()` throws on past start times instead of silently using "now"
   - `liveStreamingEnabled` returns `bool` (was `'enabled'`/`'disbaled'` typo)
   - `liveStreamTest` no longer creates side-effects
   - `getChannelDetails` returns `?array`
   - Token refresh returns refreshed token; dispatches `TokenRefreshed` event
8. **Exception types** — typed exceptions; bare `\Exception` catches still work
9. **Dependency injection** — optional but recommended
10. **Testing** — `withClient()` for mocked clients

## 16. Out of scope (for 2.0)

These are explicitly punted to later 2.x minors:
- YouTube Comments API
- YouTube Analytics API
- YouTube Reporting API
- Captions / subtitles
- Playlists
- Auto-bind via `contentDetails.boundStreamId` (newer live-streaming flow)
- Multi-tenant credential management as a first-class abstraction
- A migration command (`php artisan youtube:upgrade-from-1x`) — not worth it for a small package

## 17. Open questions

None at design-approval time. Implementation may surface specifics around:
- Exact retry/backoff thresholds for the eventual-consistency read after broadcast insert (§6 bug 15) — will be tuned during implementation.
- Whether to ship a `php artisan youtube:install` command for first-time setup ergonomics — likely yes, decided during implementation.
