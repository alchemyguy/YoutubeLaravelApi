# YoutubeLaravelApi 2.0 Modernization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Rewrite the YoutubeLaravelApi package as a clean 2.0 release on PHP 8.3+/Laravel 11+ with `google/apiclient ^2.18`, fixing 17 catalogued bugs, replacing `\Config::` facade coupling with proper dependency injection, adding a complete test suite, CI, static analysis, and a hosted documentation site.

**Architecture:** PSR-4 package targeting `Alchemyguy\YoutubeLaravelApi\` namespace. A `YoutubeClientFactory` builds `Google\Client` from config; a service provider registers everything as singletons; service classes (`AuthenticateService`, `ChannelService`, `VideoService`, `LiveStreamService`) accept an optional client in their constructor and expose `withClient()` for tests. `LiveStreamService` is split into thin orchestrator + three managers (`BroadcastManager`, `StreamManager`, `ThumbnailUploader`). Typed exceptions extend `YoutubeException`. DTOs and enums replace loose array params.

**Tech Stack:** PHP 8.3, Laravel 11, google/apiclient ^2.18, Pest 3 (PHPUnit 11), Mockery, Orchestra Testbench, PHPStan + Larastan (level 8), Laravel Pint, Rector, GitHub Actions, VitePress, phpDocumentor.

**Branch:** `feat/2.0-modernization` (already cut). Every task commits to this branch. Final PR `feat/2.0-modernization → master`.

**Spec reference:** `docs/superpowers/specs/2026-05-02-youtube-laravel-api-2.0-modernization-design.md`

---

## How to read this plan

- Tasks are numbered `N.M` where `N` is the phase. Tasks within a phase are sequential — do not skip ahead.
- Each task lists exact file paths to **Create**, **Modify**, **Delete**, or **Test**.
- Each task has 3-6 steps. Most follow strict TDD: write failing test → run it (verify fail) → implement → run test (verify pass) → commit.
- Bash commands are runnable as-is. Code blocks are the actual content to write.
- All PHP files start with `<?php\n\ndeclare(strict_types=1);\n\nnamespace …;` — assume this header even when omitted in code blocks.
- Always run `composer test:unit` before committing, even when not explicitly listed.

---

## Phase 0: Foundation setup

Goal: get the dev environment, dependencies, and tooling configs in place so subsequent phases can do TDD.

### Task 0.1: Update `composer.json`

**Files:**
- Modify: `composer.json`

- [ ] **Step 1: Replace `composer.json` contents**

```json
{
    "name": "alchemyguy/youtube-laravel-api",
    "description": "Modern Laravel wrapper for the YouTube Data API v3 with OAuth, live streaming, channels, and video uploads.",
    "keywords": ["alchemyguy", "youtube", "laravel", "youtube-api", "live-streaming", "youtube-livestream", "youtube-video", "google-api", "oauth"],
    "homepage": "https://github.com/alchemyguy/YoutubeLaravelApi",
    "license": "MIT",
    "authors": [
        {
            "name": "Mukesh Chandra",
            "email": "mukesh201722@gmail.com",
            "role": "Developer"
        }
    ],
    "require": {
        "php": "^8.3",
        "google/apiclient": "^2.18",
        "illuminate/support": "^11.0|^12.0",
        "illuminate/contracts": "^11.0|^12.0",
        "nesbot/carbon": "^2.72|^3.0"
    },
    "require-dev": {
        "pestphp/pest": "^3.0",
        "pestphp/pest-plugin-laravel": "^3.0",
        "orchestra/testbench": "^9.0|^10.0",
        "mockery/mockery": "^1.6",
        "phpstan/phpstan": "^1.11",
        "larastan/larastan": "^2.9",
        "laravel/pint": "^1.16",
        "rector/rector": "^1.2"
    },
    "autoload": {
        "psr-4": {
            "Alchemyguy\\YoutubeLaravelApi\\": "src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Alchemyguy\\YoutubeLaravelApi\\Tests\\": "tests/"
        }
    },
    "scripts": {
        "test": "pest",
        "test:unit": "pest --testsuite=Unit",
        "test:integration": "pest --group=integration",
        "test:coverage": "pest --coverage --min=95",
        "analyse": "phpstan analyse",
        "fix": "pint",
        "lint": "pint --test",
        "rector": "rector process --dry-run"
    },
    "extra": {
        "branch-alias": {
            "dev-master": "2.0-dev"
        },
        "laravel": {
            "providers": [
                "Alchemyguy\\YoutubeLaravelApi\\YoutubeLaravelApiServiceProvider"
            ]
        }
    },
    "config": {
        "sort-packages": true,
        "allow-plugins": {
            "pestphp/pest-plugin": true
        }
    },
    "minimum-stability": "stable",
    "prefer-stable": true
}
```

- [ ] **Step 2: Install dependencies**

Run: `composer update`
Expected: lockfile generated, vendor/ populated, no resolver errors.

- [ ] **Step 3: Commit**

```bash
git add composer.json composer.lock
git commit -m "build: bump to PHP 8.3, Laravel 11/12, google/apiclient ^2.18"
```

### Task 0.2: Update `.gitignore`

**Files:**
- Create or modify: `.gitignore`

- [ ] **Step 1: Write `.gitignore`**

```
/vendor/
composer.lock.bak
/.idea/
/.vscode/
/.phpunit.cache/
/.phpunit.result.cache
/.phpstan.cache/
/.pint.cache
.phpunit.xml
phpunit.xml
.env
.env.local
/build/
/coverage/
/docs/.vitepress/cache/
/docs/.vitepress/dist/
/docs/node_modules/
.DS_Store
```

Note: `composer.lock` IS committed for application packages but for libraries the convention varies. We commit it because it makes CI deterministic; users `composer update` against their own constraints.

- [ ] **Step 2: Commit**

```bash
git add .gitignore
git commit -m "chore: refresh .gitignore for new tooling"
```

### Task 0.3: Add `phpunit.xml.dist`

**Files:**
- Create: `phpunit.xml.dist`

- [ ] **Step 1: Write `phpunit.xml.dist`**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
         cacheDirectory=".phpunit.cache"
         executionOrder="random"
         beStrictAboutOutputDuringTests="true"
         failOnRisky="true"
         failOnWarning="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
        <exclude>
            <file>src/config/youtube.php</file>
        </exclude>
    </source>
    <coverage>
        <report>
            <html outputDirectory="build/coverage"/>
            <text outputFile="php://stdout" showOnlySummary="true"/>
        </report>
    </coverage>
    <groups>
        <exclude>
            <group>integration</group>
        </exclude>
    </groups>
    <php>
        <env name="APP_ENV" value="testing"/>
    </php>
</phpunit>
```

- [ ] **Step 2: Commit**

```bash
git add phpunit.xml.dist
git commit -m "test: add phpunit configuration"
```

### Task 0.4: Add Pest bootstrap

**Files:**
- Create: `tests/Pest.php`
- Create: `tests/TestCase.php`

- [ ] **Step 1: Write `tests/TestCase.php`**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests;

use Alchemyguy\YoutubeLaravelApi\YoutubeLaravelApiServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [YoutubeLaravelApiServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('youtube', [
            'app_name'     => 'TestApp',
            'client_id'    => 'test-client-id',
            'client_secret'=> 'test-client-secret',
            'api_key'      => 'test-api-key',
            'redirect_url' => 'http://localhost/callback',
            'languages'    => ['English' => 'en', 'French' => 'fr'],
        ]);
    }
}
```

- [ ] **Step 2: Write `tests/Pest.php`**

```php
<?php

declare(strict_types=1);

use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;

uses(TestCase::class)->in('Unit');
```

- [ ] **Step 3: Verify Pest sees the bootstrap**

Run: `vendor/bin/pest --version`
Expected: prints Pest 3.x version, no errors.

- [ ] **Step 4: Commit**

```bash
git add tests/Pest.php tests/TestCase.php
git commit -m "test: add Pest bootstrap and Testbench TestCase"
```

### Task 0.5: Add Pint config

**Files:**
- Create: `pint.json`

- [ ] **Step 1: Write `pint.json`**

```json
{
    "preset": "laravel",
    "rules": {
        "declare_strict_types": true,
        "strict_param": true,
        "ordered_imports": {
            "sort_algorithm": "alpha",
            "imports_order": ["class", "function", "const"]
        },
        "no_unused_imports": true,
        "phpdoc_align": {"align": "left"},
        "single_quote": true,
        "concat_space": {"spacing": "one"}
    },
    "exclude": ["docs", "build"]
}
```

- [ ] **Step 2: Commit**

```bash
git add pint.json
git commit -m "style: add pint config"
```

### Task 0.6: Add PHPStan config

**Files:**
- Create: `phpstan.neon`

- [ ] **Step 1: Write `phpstan.neon`**

```neon
includes:
    - ./vendor/larastan/larastan/extension.neon

parameters:
    level: 8
    paths:
        - src
        - tests
    excludePaths:
        - src/config/*
    tmpDir: .phpstan.cache
    treatPhpDocTypesAsCertain: false
    ignoreErrors:
        - identifier: missingType.iterableValue
```

The `missingType.iterableValue` ignore is pragmatic — Google client returns are loosely-typed `array|object` and over-specifying generic array types adds noise without value at boundaries.

- [ ] **Step 2: Commit**

```bash
git add phpstan.neon
git commit -m "chore: add phpstan level 8 config"
```

### Task 0.7: Add Rector config

**Files:**
- Create: `rector.php`

- [ ] **Step 1: Write `rector.php`**

```php
<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withSets([
        LevelSetList::UP_TO_PHP_83,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
    ])
    ->withSkip([
        __DIR__ . '/src/config',
    ]);
```

- [ ] **Step 2: Commit**

```bash
git add rector.php
git commit -m "chore: add rector config"
```

### Task 0.8: Create directory skeleton

**Files:**
- Create directories listed below

- [ ] **Step 1: Create directories with `.gitkeep` markers**

```bash
mkdir -p src/Support src/Auth src/Services/LiveStream src/DTOs src/Enums src/Events src/Exceptions src/config
mkdir -p tests/Unit/Support tests/Unit/Auth tests/Unit/Services tests/Unit/Services/LiveStream tests/Integration tests/Fixtures/youtube_responses tests/Fixtures/images
touch src/Support/.gitkeep src/Auth/.gitkeep src/Services/LiveStream/.gitkeep src/DTOs/.gitkeep src/Enums/.gitkeep src/Events/.gitkeep src/Exceptions/.gitkeep
touch tests/Fixtures/youtube_responses/.gitkeep tests/Fixtures/images/.gitkeep
```

- [ ] **Step 2: Commit**

```bash
git add src tests
git commit -m "chore: scaffold new directory structure"
```

## Phase 1: Exception hierarchy

### Task 1.1: Create exception classes

**Files:**
- Create: `src/Exceptions/YoutubeException.php`
- Create: `src/Exceptions/ConfigurationException.php`
- Create: `src/Exceptions/AuthenticationException.php`
- Create: `src/Exceptions/LiveStreamingNotEnabledException.php`
- Create: `src/Exceptions/QuotaExceededException.php`
- Create: `src/Exceptions/YoutubeApiException.php`
- Test: `tests/Unit/ExceptionHierarchyTest.php`

- [ ] **Step 1: Write the failing test**

`tests/Unit/ExceptionHierarchyTest.php`:
```php
<?php

declare(strict_types=1);

use Alchemyguy\YoutubeLaravelApi\Exceptions\AuthenticationException;
use Alchemyguy\YoutubeLaravelApi\Exceptions\ConfigurationException;
use Alchemyguy\YoutubeLaravelApi\Exceptions\LiveStreamingNotEnabledException;
use Alchemyguy\YoutubeLaravelApi\Exceptions\QuotaExceededException;
use Alchemyguy\YoutubeLaravelApi\Exceptions\YoutubeApiException;
use Alchemyguy\YoutubeLaravelApi\Exceptions\YoutubeException;

it('makes YoutubeException extend RuntimeException', function () {
    expect(new YoutubeException('x'))->toBeInstanceOf(RuntimeException::class);
});

it('makes all subclasses extend YoutubeException', function () {
    expect(new ConfigurationException('x'))->toBeInstanceOf(YoutubeException::class);
    expect(new AuthenticationException('x'))->toBeInstanceOf(YoutubeException::class);
    expect(new LiveStreamingNotEnabledException('x'))->toBeInstanceOf(YoutubeException::class);
    expect(new QuotaExceededException('x'))->toBeInstanceOf(YoutubeException::class);
    expect(new YoutubeApiException('x'))->toBeInstanceOf(YoutubeException::class);
});

it('preserves previous exception in YoutubeApiException', function () {
    $prev = new RuntimeException('underlying');
    $e = new YoutubeApiException('wrapped', 0, $prev);
    expect($e->getPrevious())->toBe($prev);
});

it('exposes Google service errors via YoutubeApiException::fromGoogleException', function () {
    $googleErr = new Google\Service\Exception('quota exceeded', 403, null, [
        ['reason' => 'quotaExceeded', 'message' => 'Daily Limit Exceeded'],
    ]);
    $wrapped = YoutubeApiException::fromGoogleException($googleErr);
    expect($wrapped)->toBeInstanceOf(QuotaExceededException::class)
        ->and($wrapped->getPrevious())->toBe($googleErr);
});

it('wraps non-quota Google errors as YoutubeApiException', function () {
    $googleErr = new Google\Service\Exception('not found', 404, null, [
        ['reason' => 'videoNotFound'],
    ]);
    $wrapped = YoutubeApiException::fromGoogleException($googleErr);
    expect($wrapped)->toBeInstanceOf(YoutubeApiException::class)
        ->and($wrapped)->not->toBeInstanceOf(QuotaExceededException::class);
});
```

- [ ] **Step 2: Run test to verify failure**

Run: `vendor/bin/pest tests/Unit/ExceptionHierarchyTest.php`
Expected: FAIL with "class not found".

- [ ] **Step 3: Write the exceptions**

`src/Exceptions/YoutubeException.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Exceptions;

use RuntimeException;

class YoutubeException extends RuntimeException
{
}
```

`src/Exceptions/ConfigurationException.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Exceptions;

class ConfigurationException extends YoutubeException
{
}
```

`src/Exceptions/AuthenticationException.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Exceptions;

class AuthenticationException extends YoutubeException
{
}
```

`src/Exceptions/LiveStreamingNotEnabledException.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Exceptions;

class LiveStreamingNotEnabledException extends YoutubeException
{
}
```

`src/Exceptions/QuotaExceededException.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Exceptions;

class QuotaExceededException extends YoutubeApiException
{
}
```

`src/Exceptions/YoutubeApiException.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Exceptions;

use Google\Service\Exception as GoogleServiceException;
use Throwable;

class YoutubeApiException extends YoutubeException
{
    /** @var array<int, array<string, mixed>> */
    protected array $googleErrors = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $googleErrors = [],
    ) {
        parent::__construct($message, $code, $previous);
        $this->googleErrors = $googleErrors;
    }

    public static function fromGoogleException(GoogleServiceException $e): self
    {
        $errors = $e->getErrors() ?? [];
        $reasons = array_column($errors, 'reason');

        if (in_array('quotaExceeded', $reasons, true) || in_array('rateLimitExceeded', $reasons, true)) {
            return new QuotaExceededException($e->getMessage(), $e->getCode(), $e, $errors);
        }

        if (in_array('liveStreamingNotEnabled', $reasons, true) || in_array('liveBroadcastNotFound', $reasons, true)) {
            return new self($e->getMessage(), $e->getCode(), $e, $errors);
        }

        return new self($e->getMessage(), $e->getCode(), $e, $errors);
    }

    /** @return array<int, array<string, mixed>> */
    public function getGoogleErrors(): array
    {
        return $this->googleErrors;
    }
}
```

- [ ] **Step 4: Run tests to verify pass**

Run: `vendor/bin/pest tests/Unit/ExceptionHierarchyTest.php`
Expected: PASS — all 5 assertions green.

- [ ] **Step 5: Commit**

```bash
git add src/Exceptions tests/Unit/ExceptionHierarchyTest.php
git commit -m "feat: add typed exception hierarchy"
```

---

## Phase 2: Configuration, factory, and service provider

### Task 2.1: Move and rename config file

**Files:**
- Create: `src/config/youtube.php`
- Delete: `src/config/google-config.php`

- [ ] **Step 1: Write new config**

`src/config/youtube.php`:
```php
<?php

declare(strict_types=1);

return [
    'app_name'      => env('YOUTUBE_APP_NAME', 'YoutubeLaravelApi'),
    'client_id'     => env('YOUTUBE_CLIENT_ID'),
    'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
    'api_key'       => env('YOUTUBE_API_KEY'),
    'redirect_url'  => env('YOUTUBE_REDIRECT_URL'),

    /*
    |--------------------------------------------------------------------------
    | YouTube language codes
    |--------------------------------------------------------------------------
    | Map of human-readable language names to YouTube language codes used for
    | broadcast metadata (defaultLanguage, defaultAudioLanguage).
    */
    'languages' => [
        'Afrikaans' => 'af', 'Albanian' => 'sq', 'Amharic' => 'am', 'Arabic' => 'ar',
        'Armenian' => 'hy', 'Azerbaijani' => 'az', 'Bangla' => 'bn', 'Basque' => 'eu',
        'Belarusian' => 'be', 'Bosnian' => 'bs', 'Bulgarian' => 'bg', 'Catalan' => 'ca',
        'Chinese' => 'zh-CN', 'Chinese (Hong Kong)' => 'zh-HK', 'Chinese (Taiwan)' => 'zh-TW',
        'Croatian' => 'hr', 'Czech' => 'cs', 'Danish' => 'da', 'Dutch' => 'nl',
        'English' => 'en', 'English (United Kingdom)' => 'en-GB', 'Estonian' => 'et',
        'Filipino' => 'fil', 'Finnish' => 'fi', 'French' => 'fr', 'French (Canada)' => 'fr-CA',
        'Galician' => 'gl', 'Georgian' => 'ka', 'German' => 'de', 'Greek' => 'el',
        'Gujarati' => 'gu', 'Hebrew' => 'iw', 'Hindi' => 'hi', 'Hungarian' => 'hu',
        'Icelandic' => 'is', 'Indonesian' => 'id', 'Italian' => 'it', 'Japanese' => 'ja',
        'Kannada' => 'kn', 'Kazakh' => 'kk', 'Khmer' => 'km', 'Korean' => 'ko',
        'Kyrgyz' => 'ky', 'Lao' => 'lo', 'Latvian' => 'lv', 'Lithuanian' => 'lt',
        'Macedonian' => 'mk', 'Malay' => 'ms', 'Malayalam' => 'ml', 'Marathi' => 'mr',
        'Mongolian' => 'mn', 'Myanmar (Burmese)' => 'my', 'Nepali' => 'ne', 'Norwegian' => 'no',
        'Persian' => 'fa', 'Polish' => 'pl', 'Portuguese (Brazil)' => 'pt',
        'Portuguese (Portugal)' => 'pt-PT', 'Punjabi' => 'pa', 'Romanian' => 'ro',
        'Russian' => 'ru', 'Serbian' => 'sr', 'Serbian (Latin)' => 'sr-Latn',
        'Sinhala' => 'si', 'Slovak' => 'sk', 'Slovenian' => 'sl',
        'Spanish (Latin America)' => 'es-419', 'Spanish (Spain)' => 'es',
        'Spanish (United States)' => 'es-US', 'Swahili' => 'sw', 'Swedish' => 'sv',
        'Tamil' => 'ta', 'Telugu' => 'te', 'Thai' => 'th', 'Turkish' => 'tr',
        'Ukrainian' => 'uk', 'Urdu' => 'ur', 'Uzbek' => 'uz', 'Vietnamese' => 'vi',
        'Zulu' => 'zu',
    ],
];
```

- [ ] **Step 2: Delete old config**

```bash
git rm src/config/google-config.php
```

- [ ] **Step 3: Commit**

```bash
git add src/config/youtube.php
git commit -m "feat: rename config to youtube.php with uppercase env vars"
```

### Task 2.2: Create `YoutubeClientFactory`

**Files:**
- Create: `src/Support/YoutubeClientFactory.php`
- Test: `tests/Unit/Support/YoutubeClientFactoryTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Support;

use Alchemyguy\YoutubeLaravelApi\Exceptions\ConfigurationException;
use Alchemyguy\YoutubeLaravelApi\Support\YoutubeClientFactory;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Client;

final class YoutubeClientFactoryTest extends TestCase
{
    public function test_make_returns_configured_google_client(): void
    {
        $factory = new YoutubeClientFactory([
            'app_name'     => 'TestApp',
            'client_id'    => 'cid',
            'client_secret'=> 'csec',
            'api_key'      => 'k',
            'redirect_url' => 'http://localhost/cb',
        ]);

        $client = $factory->make();

        $this->assertInstanceOf(Client::class, $client);
        $this->assertSame('cid', $client->getClientId());
        $this->assertSame('http://localhost/cb', $client->getRedirectUri());
        $this->assertContains('https://www.googleapis.com/auth/youtube', $client->getScopes());
    }

    public function test_make_throws_when_required_keys_missing(): void
    {
        $this->expectException(ConfigurationException::class);
        $this->expectExceptionMessage('client_id');

        (new YoutubeClientFactory(['client_secret' => 'csec', 'redirect_url' => 'http://x']))->make();
    }

    public function test_make_sets_offline_access_and_consent_prompt(): void
    {
        $factory = new YoutubeClientFactory([
            'client_id'    => 'cid',
            'client_secret'=> 'csec',
            'redirect_url' => 'http://localhost/cb',
        ]);
        $client = $factory->make();
        $authUrl = $client->createAuthUrl();
        $this->assertStringContainsString('access_type=offline', $authUrl);
        $this->assertStringContainsString('prompt=consent', $authUrl);
    }
}
```

- [ ] **Step 2: Run test to verify failure**

Run: `vendor/bin/pest tests/Unit/Support/YoutubeClientFactoryTest.php`
Expected: FAIL ("class not found").

- [ ] **Step 3: Write the factory**

`src/Support/YoutubeClientFactory.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Support;

use Alchemyguy\YoutubeLaravelApi\Exceptions\ConfigurationException;
use Google\Client;

final class YoutubeClientFactory
{
    /** @param array<string, mixed> $config */
    public function __construct(private readonly array $config) {}

    public function make(): Client
    {
        $this->assertRequired(['client_id', 'client_secret', 'redirect_url']);

        $client = new Client();
        $client->setApplicationName((string) ($this->config['app_name'] ?? 'YoutubeLaravelApi'));
        $client->setClientId((string) $this->config['client_id']);
        $client->setClientSecret((string) $this->config['client_secret']);
        $client->setRedirectUri((string) $this->config['redirect_url']);

        if (!empty($this->config['api_key'])) {
            $client->setDeveloperKey((string) $this->config['api_key']);
        }

        $client->setScopes(['https://www.googleapis.com/auth/youtube']);
        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }

    /** @param list<string> $keys */
    private function assertRequired(array $keys): void
    {
        foreach ($keys as $key) {
            if (empty($this->config[$key])) {
                throw new ConfigurationException(
                    "Missing required YouTube config key: {$key}. Set YOUTUBE_" . strtoupper($key) . ' in your .env.'
                );
            }
        }
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Support/YoutubeClientFactoryTest.php`
Expected: PASS (3 assertions).

- [ ] **Step 5: Commit**

```bash
git add src/Support/YoutubeClientFactory.php tests/Unit/Support/YoutubeClientFactoryTest.php
git commit -m "feat: YoutubeClientFactory builds Google\\Client from config"
```

### Task 2.3: Update service provider

**Files:**
- Modify: `src/YoutubeLaravelApiServiceProvider.php`
- Test: `tests/Unit/ServiceProviderTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit;

use Alchemyguy\YoutubeLaravelApi\Support\YoutubeClientFactory;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;

final class ServiceProviderTest extends TestCase
{
    public function test_factory_resolves_as_singleton(): void
    {
        $a = $this->app->make(YoutubeClientFactory::class);
        $b = $this->app->make(YoutubeClientFactory::class);
        $this->assertSame($a, $b);
    }

    public function test_factory_uses_youtube_config(): void
    {
        $factory = $this->app->make(YoutubeClientFactory::class);
        $client = $factory->make();
        $this->assertSame('test-client-id', $client->getClientId());
    }

    public function test_publishes_config_under_youtube_config_tag(): void
    {
        $paths = \Illuminate\Support\ServiceProvider::pathsToPublish(
            \Alchemyguy\YoutubeLaravelApi\YoutubeLaravelApiServiceProvider::class,
            'youtube-config'
        );
        $this->assertNotEmpty($paths);
    }
}
```

- [ ] **Step 2: Run test to verify failure**

Run: `vendor/bin/pest tests/Unit/ServiceProviderTest.php`
Expected: FAIL.

- [ ] **Step 3: Rewrite the service provider**

`src/YoutubeLaravelApiServiceProvider.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi;

use Alchemyguy\YoutubeLaravelApi\Support\YoutubeClientFactory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\ServiceProvider;

class YoutubeLaravelApiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/youtube.php', 'youtube');

        $this->app->singleton(YoutubeClientFactory::class, function ($app): YoutubeClientFactory {
            /** @var Repository $config */
            $config = $app->make('config');
            return new YoutubeClientFactory($config->get('youtube', []));
        });
    }

    public function boot(): void
    {
        $this->publishes(
            [__DIR__ . '/config/youtube.php' => config_path('youtube.php')],
            'youtube-config'
        );
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/ServiceProviderTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/YoutubeLaravelApiServiceProvider.php tests/Unit/ServiceProviderTest.php
git commit -m "feat: service provider registers factory singleton + merges config"
```

## Phase 3: Support helpers

### Task 3.1: `ResourceBuilder` (replaces `createResource` / `addPropertyToResource`)

**Files:**
- Create: `src/Support/ResourceBuilder.php`
- Test: `tests/Unit/Support/ResourceBuilderTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Support;

use Alchemyguy\YoutubeLaravelApi\Support\ResourceBuilder;
use PHPUnit\Framework\TestCase;

final class ResourceBuilderTest extends TestCase
{
    public function test_builds_simple_dotted_resource(): void
    {
        $r = ResourceBuilder::fromProperties([
            'id' => 'abc',
            'snippet.title' => 'My Title',
        ]);
        $this->assertSame(['id' => 'abc', 'snippet' => ['title' => 'My Title']], $r);
    }

    public function test_handles_array_marker_with_csv_value(): void
    {
        $r = ResourceBuilder::fromProperties(['snippet.tags[]' => 'a, b, c']);
        $this->assertSame(['snippet' => ['tags' => ['a', ' b', ' c']]], $r);
    }

    public function test_skips_falsy_values(): void
    {
        $r = ResourceBuilder::fromProperties([
            'a' => 'x',
            'b' => '',
            'c' => null,
            'd' => 0,
        ]);
        $this->assertSame(['a' => 'x'], $r);
    }

    public function test_array_marker_with_empty_value_returns_empty_array(): void
    {
        $r = ResourceBuilder::fromProperties(['tags[]' => null]);
        $this->assertSame([], $r);
    }
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Support/ResourceBuilderTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

`src/Support/ResourceBuilder.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Support;

final class ResourceBuilder
{
    /**
     * Convert dot-notation properties (e.g. "snippet.tags[]" => "a,b,c")
     * into a nested array for Google API resources.
     *
     * @param array<string, mixed> $properties
     * @return array<string, mixed>
     */
    public static function fromProperties(array $properties): array
    {
        $resource = [];

        foreach ($properties as $path => $value) {
            if (empty($value)) {
                continue;
            }
            self::set($resource, $path, $value);
        }

        return $resource;
    }

    /**
     * @param array<string, mixed> $resource
     */
    private static function set(array &$resource, string $path, mixed $value): void
    {
        $keys = explode('.', $path);
        $ref = &$resource;
        $isArray = false;

        foreach ($keys as $key) {
            if (str_ends_with($key, '[]')) {
                $key = substr($key, 0, -2);
                $isArray = true;
            }
            $ref = &$ref[$key];
        }

        if ($isArray) {
            $ref = is_string($value) ? explode(',', $value) : (array) $value;
        } else {
            $ref = $value;
        }
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Support/ResourceBuilderTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Support/ResourceBuilder.php tests/Unit/Support/ResourceBuilderTest.php
git commit -m "feat: ResourceBuilder for dot-notation API resources"
```

### Task 3.2: `DurationParser` (replaces `parseTime`)

**Files:**
- Create: `src/Support/DurationParser.php`
- Test: `tests/Unit/Support/DurationParserTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Support;

use Alchemyguy\YoutubeLaravelApi\Support\DurationParser;
use PHPUnit\Framework\TestCase;

final class DurationParserTest extends TestCase
{
    public function test_parses_full_iso8601_duration(): void
    {
        $this->assertSame('1 Hours 30 Minutes 5 Seconds', DurationParser::toHuman('PT1H30M5S'));
    }

    public function test_parses_partial_duration(): void
    {
        $this->assertSame('45 Minutes', DurationParser::toHuman('PT45M'));
    }

    public function test_returns_empty_for_blank(): void
    {
        $this->assertSame('', DurationParser::toHuman(''));
    }
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Support/DurationParserTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

`src/Support/DurationParser.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Support;

final class DurationParser
{
    /**
     * Convert YouTube ISO 8601 duration (e.g. PT1H30M5S) to a human-readable string.
     */
    public static function toHuman(string $iso): string
    {
        if ($iso === '') {
            return '';
        }
        $out = preg_replace(
            ['/^PT/', '/(\d+)H/', '/(\d+)M/', '/(\d+)S/'],
            ['', '$1 Hours ', '$1 Minutes ', '$1 Seconds'],
            $iso
        );
        return trim((string) $out);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Support/DurationParserTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Support/DurationParser.php tests/Unit/Support/DurationParserTest.php
git commit -m "feat: DurationParser for ISO 8601 duration strings"
```

---

## Phase 4: Enums and DTOs

### Task 4.1: Create three enums

**Files:**
- Create: `src/Enums/BroadcastStatus.php`
- Create: `src/Enums/Rating.php`
- Create: `src/Enums/PrivacyStatus.php`
- Test: `tests/Unit/EnumsTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

use Alchemyguy\YoutubeLaravelApi\Enums\BroadcastStatus;
use Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus;
use Alchemyguy\YoutubeLaravelApi\Enums\Rating;

it('exposes broadcast statuses', function () {
    expect(BroadcastStatus::Testing->value)->toBe('testing');
    expect(BroadcastStatus::Live->value)->toBe('live');
    expect(BroadcastStatus::Complete->value)->toBe('complete');
});

it('exposes ratings', function () {
    expect(Rating::Like->value)->toBe('like');
    expect(Rating::Dislike->value)->toBe('dislike');
    expect(Rating::None->value)->toBe('none');
});

it('exposes privacy statuses', function () {
    expect(PrivacyStatus::Public->value)->toBe('public');
    expect(PrivacyStatus::Private->value)->toBe('private');
    expect(PrivacyStatus::Unlisted->value)->toBe('unlisted');
});
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/EnumsTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement enums**

`src/Enums/BroadcastStatus.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Enums;

enum BroadcastStatus: string
{
    case Testing = 'testing';
    case Live = 'live';
    case Complete = 'complete';
}
```

`src/Enums/Rating.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Enums;

enum Rating: string
{
    case Like = 'like';
    case Dislike = 'dislike';
    case None = 'none';
}
```

`src/Enums/PrivacyStatus.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Enums;

enum PrivacyStatus: string
{
    case Public = 'public';
    case Private = 'private';
    case Unlisted = 'unlisted';
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/EnumsTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Enums tests/Unit/EnumsTest.php
git commit -m "feat: add BroadcastStatus, Rating, PrivacyStatus enums"
```

### Task 4.2: Create `BroadcastData` DTO

**Files:**
- Create: `src/DTOs/BroadcastData.php`
- Test: `tests/Unit/DTOs/BroadcastDataTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\DTOs;

use Alchemyguy\YoutubeLaravelApi\DTOs\BroadcastData;
use Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus;
use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class BroadcastDataTest extends TestCase
{
    public function test_constructs_with_minimal_fields(): void
    {
        $start = new DateTimeImmutable('+1 hour');
        $data = new BroadcastData(
            title: 'Hello',
            description: 'World',
            scheduledStartTime: $start,
        );
        $this->assertSame('Hello', $data->title);
        $this->assertSame('World', $data->description);
        $this->assertSame(PrivacyStatus::Public, $data->privacyStatus);
        $this->assertSame('English', $data->languageName);
        $this->assertSame([], $data->tags);
    }

    public function test_throws_when_start_is_in_past(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('past');
        new BroadcastData(
            title: 'X',
            description: 'Y',
            scheduledStartTime: new DateTimeImmutable('-1 hour'),
        );
    }

    public function test_throws_when_end_before_start(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('end');
        new BroadcastData(
            title: 'X',
            description: 'Y',
            scheduledStartTime: new DateTimeImmutable('+2 hour'),
            scheduledEndTime: new DateTimeImmutable('+1 hour'),
        );
    }

    public function test_throws_when_tag_total_length_exceeds_500(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('500');
        new BroadcastData(
            title: 'X',
            description: 'Y',
            scheduledStartTime: new DateTimeImmutable('+1 hour'),
            tags: [str_repeat('a', 501)],
        );
    }

    public function test_from_array_constructs_dto(): void
    {
        $data = BroadcastData::fromArray([
            'title' => 'X',
            'description' => 'Y',
            'event_start_date_time' => (new DateTimeImmutable('+1 hour'))->format('Y-m-d H:i:s'),
            'time_zone' => 'UTC',
            'privacy_status' => 'private',
            'language_name' => 'French',
            'tag_array' => ['x', 'y'],
        ]);
        $this->assertSame('X', $data->title);
        $this->assertSame(PrivacyStatus::Private, $data->privacyStatus);
        $this->assertSame('French', $data->languageName);
        $this->assertSame(['x', 'y'], $data->tags);
    }
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/DTOs/BroadcastDataTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement DTO**

`src/DTOs/BroadcastData.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\DTOs;

use Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class BroadcastData
{
    /**
     * @param array<int, string> $tags
     */
    public function __construct(
        public string $title,
        public string $description,
        public DateTimeImmutable $scheduledStartTime,
        public ?DateTimeImmutable $scheduledEndTime = null,
        public PrivacyStatus $privacyStatus = PrivacyStatus::Public,
        public string $languageName = 'English',
        public ?string $thumbnailPath = null,
        public array $tags = [],
    ) {
        $now = new DateTimeImmutable('now', $scheduledStartTime->getTimezone());
        if ($scheduledStartTime < $now) {
            throw new InvalidArgumentException(
                "scheduledStartTime ({$scheduledStartTime->format(DATE_ATOM)}) is in the past."
            );
        }
        if ($scheduledEndTime !== null && $scheduledEndTime <= $scheduledStartTime) {
            throw new InvalidArgumentException(
                'scheduledEndTime must be after scheduledStartTime.'
            );
        }
        $totalTagLen = array_sum(array_map('strlen', $tags));
        if ($totalTagLen > 500) {
            throw new InvalidArgumentException(
                "Total tag length ({$totalTagLen}) exceeds YouTube's 500-character limit."
            );
        }
        if ($thumbnailPath !== null && !is_file($thumbnailPath)) {
            throw new InvalidArgumentException("Thumbnail not found: {$thumbnailPath}");
        }
    }

    /**
     * Backward-compatible constructor for 1.x-style array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        foreach (['title', 'description', 'event_start_date_time', 'time_zone'] as $required) {
            if (!isset($data[$required]) || $data[$required] === '') {
                throw new InvalidArgumentException("Missing required field: {$required}");
            }
        }

        $tz = new DateTimeZone((string) $data['time_zone']);
        $start = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            (string) $data['event_start_date_time'],
            $tz
        );
        if ($start === false) {
            throw new InvalidArgumentException(
                "Invalid event_start_date_time '{$data['event_start_date_time']}' — expected Y-m-d H:i:s."
            );
        }

        $end = null;
        if (!empty($data['event_end_date_time'])) {
            $end = DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                (string) $data['event_end_date_time'],
                $tz
            );
            if ($end === false) {
                throw new InvalidArgumentException(
                    "Invalid event_end_date_time '{$data['event_end_date_time']}' — expected Y-m-d H:i:s."
                );
            }
        }

        return new self(
            title: (string) $data['title'],
            description: (string) $data['description'],
            scheduledStartTime: $start,
            scheduledEndTime: $end,
            privacyStatus: PrivacyStatus::from((string) ($data['privacy_status'] ?? 'public')),
            languageName: (string) ($data['language_name'] ?? 'English'),
            thumbnailPath: isset($data['thumbnail_path']) ? (string) $data['thumbnail_path'] : null,
            tags: array_values(array_filter((array) ($data['tag_array'] ?? []), 'strlen')),
        );
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/DTOs/BroadcastDataTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/DTOs/BroadcastData.php tests/Unit/DTOs/BroadcastDataTest.php
git commit -m "feat: BroadcastData DTO with validation"
```

### Task 4.3: Create `VideoUploadData` DTO

**Files:**
- Create: `src/DTOs/VideoUploadData.php`
- Test: `tests/Unit/DTOs/VideoUploadDataTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\DTOs;

use Alchemyguy\YoutubeLaravelApi\DTOs\VideoUploadData;
use Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class VideoUploadDataTest extends TestCase
{
    public function test_constructs_with_required_fields(): void
    {
        $d = new VideoUploadData(
            title: 't',
            description: 'd',
            categoryId: '22',
            privacyStatus: PrivacyStatus::Private,
            tags: ['a', 'b'],
        );
        $this->assertSame('t', $d->title);
        $this->assertSame(PrivacyStatus::Private, $d->privacyStatus);
        $this->assertSame(1024 * 1024, $d->chunkSizeBytes);
    }

    public function test_throws_on_chunk_size_below_256kb(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new VideoUploadData('t', 'd', '22', PrivacyStatus::Public, [], 1024);
    }

    public function test_from_array_translates_1x_keys(): void
    {
        $d = VideoUploadData::fromArray([
            'title' => 't',
            'description' => 'd',
            'tags' => ['a'],
            'category_id' => '22',
            'video_status' => 'unlisted',
        ]);
        $this->assertSame(PrivacyStatus::Unlisted, $d->privacyStatus);
        $this->assertSame(['a'], $d->tags);
    }
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/DTOs/VideoUploadDataTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

`src/DTOs/VideoUploadData.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\DTOs;

use Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus;
use InvalidArgumentException;

final readonly class VideoUploadData
{
    /**
     * @param array<int, string> $tags
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $categoryId,
        public PrivacyStatus $privacyStatus,
        public array $tags = [],
        public int $chunkSizeBytes = 1048576,
    ) {
        if ($chunkSizeBytes < 262144) {
            throw new InvalidArgumentException('chunkSizeBytes must be >= 256 KiB (262144).');
        }
        if ($title === '') {
            throw new InvalidArgumentException('title is required.');
        }
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        foreach (['title', 'description', 'category_id', 'video_status'] as $key) {
            if (!isset($data[$key])) {
                throw new InvalidArgumentException("Missing required field: {$key}");
            }
        }
        return new self(
            title: (string) $data['title'],
            description: (string) $data['description'],
            categoryId: (string) $data['category_id'],
            privacyStatus: PrivacyStatus::from((string) $data['video_status']),
            tags: array_values((array) ($data['tags'] ?? [])),
            chunkSizeBytes: (int) ($data['chunk_size'] ?? 1048576),
        );
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/DTOs/VideoUploadDataTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/DTOs/VideoUploadData.php tests/Unit/DTOs/VideoUploadDataTest.php
git commit -m "feat: VideoUploadData DTO"
```

### Task 4.4: Create `BrandingProperties` DTO (thin wrapper)

**Files:**
- Create: `src/DTOs/BrandingProperties.php`
- Test: `tests/Unit/DTOs/BrandingPropertiesTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\DTOs;

use Alchemyguy\YoutubeLaravelApi\DTOs\BrandingProperties;
use PHPUnit\Framework\TestCase;

final class BrandingPropertiesTest extends TestCase
{
    public function test_to_dotted_array_returns_id_and_branding_keys(): void
    {
        $b = new BrandingProperties(
            channelId: 'UCxyz',
            description: 'desc',
            keywords: 'a,b',
            defaultLanguage: 'en',
        );
        $this->assertSame([
            'id' => 'UCxyz',
            'brandingSettings.channel.description' => 'desc',
            'brandingSettings.channel.keywords' => 'a,b',
            'brandingSettings.channel.defaultLanguage' => 'en',
        ], $b->toDottedArray());
    }

    public function test_omits_null_values(): void
    {
        $b = new BrandingProperties(channelId: 'UC1', description: 'd');
        $this->assertSame([
            'id' => 'UC1',
            'brandingSettings.channel.description' => 'd',
        ], $b->toDottedArray());
    }
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/DTOs/BrandingPropertiesTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

`src/DTOs/BrandingProperties.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\DTOs;

final readonly class BrandingProperties
{
    public function __construct(
        public string $channelId,
        public ?string $description = null,
        public ?string $keywords = null,
        public ?string $defaultLanguage = null,
        public ?string $defaultTab = null,
        public ?bool $moderateComments = null,
        public ?bool $showRelatedChannels = null,
        public ?bool $showBrowseView = null,
        public ?string $featuredChannelsTitle = null,
        public ?string $featuredChannelsUrls = null,
        public ?string $unsubscribedTrailer = null,
    ) {}

    /** @return array<string, mixed> */
    public function toDottedArray(): array
    {
        $map = [
            'description' => 'brandingSettings.channel.description',
            'keywords' => 'brandingSettings.channel.keywords',
            'defaultLanguage' => 'brandingSettings.channel.defaultLanguage',
            'defaultTab' => 'brandingSettings.channel.defaultTab',
            'moderateComments' => 'brandingSettings.channel.moderateComments',
            'showRelatedChannels' => 'brandingSettings.channel.showRelatedChannels',
            'showBrowseView' => 'brandingSettings.channel.showBrowseView',
            'featuredChannelsTitle' => 'brandingSettings.channel.featuredChannelsTitle',
            'featuredChannelsUrls' => 'brandingSettings.channel.featuredChannelsUrls[]',
            'unsubscribedTrailer' => 'brandingSettings.channel.unsubscribedTrailer',
        ];
        $out = ['id' => $this->channelId];
        foreach ($map as $prop => $dotted) {
            if ($this->{$prop} !== null) {
                $out[$dotted] = $this->{$prop};
            }
        }
        return $out;
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/DTOs/BrandingPropertiesTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/DTOs/BrandingProperties.php tests/Unit/DTOs/BrandingPropertiesTest.php
git commit -m "feat: BrandingProperties DTO"
```

## Phase 5: Events

### Task 5.1: `TokenRefreshed` event

**Files:**
- Create: `src/Events/TokenRefreshed.php`
- Test: `tests/Unit/Events/TokenRefreshedTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Events;

use Alchemyguy\YoutubeLaravelApi\Events\TokenRefreshed;
use PHPUnit\Framework\TestCase;

final class TokenRefreshedTest extends TestCase
{
    public function test_holds_old_and_new_tokens(): void
    {
        $event = new TokenRefreshed(['access_token' => 'old'], ['access_token' => 'new']);
        $this->assertSame('old', $event->oldToken['access_token']);
        $this->assertSame('new', $event->newToken['access_token']);
    }
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Events/TokenRefreshedTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

`src/Events/TokenRefreshed.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Events;

final readonly class TokenRefreshed
{
    /**
     * @param array<string, mixed> $oldToken
     * @param array<string, mixed> $newToken
     */
    public function __construct(
        public array $oldToken,
        public array $newToken,
    ) {}
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Events/TokenRefreshedTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Events/TokenRefreshed.php tests/Unit/Events/TokenRefreshedTest.php
git commit -m "feat: TokenRefreshed event"
```

---

## Phase 6: OAuth service

### Task 6.1: Create `OAuthService`

**Files:**
- Create: `src/Auth/OAuthService.php`
- Test: `tests/Unit/Auth/OAuthServiceTest.php`

This replaces the old `Auth\AuthService`. Token refresh now returns the refreshed token and dispatches `TokenRefreshed`. No more loose `\Config::` calls — the client comes in via constructor.

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Auth;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\Events\TokenRefreshed;
use Alchemyguy\YoutubeLaravelApi\Exceptions\AuthenticationException;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Client;
use Illuminate\Support\Facades\Event;
use Mockery;

final class OAuthServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_get_login_url_sets_state_and_login_hint(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setState')->once()->with('chan-1');
        $client->shouldReceive('setLoginHint')->once()->with('user@example.com');
        $client->shouldReceive('createAuthUrl')->once()->andReturn('https://accounts.google.com/o/oauth2/v2/auth?x=y');

        $svc = new OAuthService($client);
        $this->assertSame(
            'https://accounts.google.com/o/oauth2/v2/auth?x=y',
            $svc->getLoginUrl('user@example.com', 'chan-1')
        );
    }

    public function test_get_login_url_skips_state_when_channel_id_missing(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldNotReceive('setState');
        $client->shouldReceive('setLoginHint')->once();
        $client->shouldReceive('createAuthUrl')->once()->andReturn('url');
        (new OAuthService($client))->getLoginUrl('user@example.com');
    }

    public function test_exchange_code_returns_token(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('fetchAccessTokenWithAuthCode')->once()->with('code123')
            ->andReturn(['access_token' => 'tok', 'refresh_token' => 'rt']);
        $token = (new OAuthService($client))->exchangeCode('code123');
        $this->assertSame('tok', $token['access_token']);
    }

    public function test_exchange_code_throws_on_error_response(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('fetchAccessTokenWithAuthCode')->once()
            ->andReturn(['error' => 'invalid_grant', 'error_description' => 'bad']);
        $this->expectException(AuthenticationException::class);
        (new OAuthService($client))->exchangeCode('bad');
    }

    public function test_set_access_token_dispatches_event_on_refresh(): void
    {
        Event::fake();
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setAccessToken')->once();
        $client->shouldReceive('isAccessTokenExpired')->andReturn(true, false);
        $client->shouldReceive('getRefreshToken')->andReturn('refresh-tok');
        $client->shouldReceive('fetchAccessTokenWithRefreshToken')->once()->with('refresh-tok')
            ->andReturn(['access_token' => 'new']);
        $client->shouldReceive('getAccessToken')->andReturn(['access_token' => 'new']);

        $svc = new OAuthService($client);
        $newToken = $svc->setAccessToken(['access_token' => 'old', 'refresh_token' => 'refresh-tok']);

        $this->assertSame(['access_token' => 'new'], $newToken);
        Event::assertDispatched(TokenRefreshed::class);
    }

    public function test_set_access_token_returns_null_when_not_refreshed(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setAccessToken')->once();
        $client->shouldReceive('isAccessTokenExpired')->andReturn(false);
        $svc = new OAuthService($client);
        $this->assertNull($svc->setAccessToken(['access_token' => 'still-good']));
    }
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Auth/OAuthServiceTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

`src/Auth/OAuthService.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Auth;

use Alchemyguy\YoutubeLaravelApi\Events\TokenRefreshed;
use Alchemyguy\YoutubeLaravelApi\Exceptions\AuthenticationException;
use Google\Client;
use Illuminate\Support\Facades\Event;

class OAuthService
{
    public function __construct(protected Client $client) {}

    public function client(): Client
    {
        return $this->client;
    }

    public function getLoginUrl(string $youtubeEmail, ?string $channelId = null): string
    {
        if ($channelId !== null && $channelId !== '') {
            $this->client->setState($channelId);
        }
        $this->client->setLoginHint($youtubeEmail);
        return $this->client->createAuthUrl();
    }

    /**
     * @return array<string, mixed>
     */
    public function exchangeCode(string $code): array
    {
        $token = $this->client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            $msg = (string) ($token['error_description'] ?? $token['error']);
            throw new AuthenticationException("Token exchange failed: {$msg}");
        }

        return $token;
    }

    /**
     * Apply the token to the client; refresh if expired.
     * Returns the refreshed token if a refresh occurred (caller should persist),
     * or null if the existing token was still valid.
     *
     * @param array<string, mixed> $token
     * @return array<string, mixed>|null
     */
    public function setAccessToken(array $token): ?array
    {
        $this->client->setAccessToken($token);

        if (!$this->client->isAccessTokenExpired()) {
            return null;
        }

        $refreshToken = $token['refresh_token'] ?? $this->client->getRefreshToken();
        if (empty($refreshToken)) {
            throw new AuthenticationException('Access token expired and no refresh token available.');
        }

        $refreshed = $this->client->fetchAccessTokenWithRefreshToken((string) $refreshToken);
        if (isset($refreshed['error'])) {
            $msg = (string) ($refreshed['error_description'] ?? $refreshed['error']);
            throw new AuthenticationException("Token refresh failed: {$msg}");
        }

        $newToken = $this->client->getAccessToken();
        if (!is_array($newToken)) {
            throw new AuthenticationException('Token refresh returned invalid token shape.');
        }

        Event::dispatch(new TokenRefreshed($token, $newToken));
        return $newToken;
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Auth/OAuthServiceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Auth/OAuthService.php tests/Unit/Auth/OAuthServiceTest.php
git commit -m "feat: OAuthService with token-refresh event"
```

---

## Phase 7: Channel service

### Note on test fixtures

Many tests in Phases 7-10 mock `Google\Service\YouTube` and its sub-resources (`channels`, `videos`, `liveBroadcasts`, `liveStreams`, `subscriptions`, `thumbnails`, `search`). The standard fixture file `tests/Fixtures/youtube_responses/<resource>_<verb>.json` holds a representative response for each call. **Capture these from the live API once during integration testing** (Phase 14) and commit; until then, hand-write minimal valid JSON shapes per the YouTube API docs.

For pattern consistency, every service in Phases 7-9 follows this template:
- Constructor: `public function __construct(private OAuthService $oauth)`
- Static factory: `public static function withClient(Google\Client $client): self`
- Default factory resolution: `public function __construct(?OAuthService $oauth = null)` resolves from container if null. We'll generate the boilerplate via a `BaseService` trait-like helper.

### Task 7.0: Create `BaseService` (shared pattern)

**Files:**
- Create: `src/Services/BaseService.php`
- Test: `tests/Unit/Services/BaseServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\Exceptions\QuotaExceededException;
use Alchemyguy\YoutubeLaravelApi\Exceptions\YoutubeApiException;
use Alchemyguy\YoutubeLaravelApi\Services\BaseService;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Client;
use Mockery;

final class BaseServiceTest extends TestCase
{
    public function test_constructs_with_explicit_oauth_service(): void
    {
        $oauth = Mockery::mock(OAuthService::class);
        $svc = new class ($oauth) extends BaseService {};
        $this->assertSame($oauth, $svc->oauth());
    }

    public function test_resolves_oauth_from_container_when_no_args(): void
    {
        $svc = new class extends BaseService {};
        $this->assertInstanceOf(OAuthService::class, $svc->oauth());
    }

    public function test_with_client_creates_isolated_instance(): void
    {
        $client = Mockery::mock(Client::class);
        $concrete = new class extends BaseService {};
        $built = $concrete::withClient($client);
        $this->assertSame($client, $built->client());
    }

    public function test_call_wraps_google_quota_exception_as_quota_exceeded(): void
    {
        $svc = new class extends BaseService {
            public function run(\Closure $fn): mixed { return $this->call($fn); }
        };

        $this->expectException(QuotaExceededException::class);
        $svc->run(fn () => throw new \Google\Service\Exception(
            'quota exceeded', 403, null, [['reason' => 'quotaExceeded']]
        ));
    }

    public function test_call_wraps_other_throwables_as_youtube_api_exception(): void
    {
        $svc = new class extends BaseService {
            public function run(\Closure $fn): mixed { return $this->call($fn); }
        };

        $this->expectException(YoutubeApiException::class);
        $svc->run(fn () => throw new \RuntimeException('boom'));
    }
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Services/BaseServiceTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

`src/Services/BaseService.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\Exceptions\YoutubeApiException;
use Alchemyguy\YoutubeLaravelApi\Support\YoutubeClientFactory;
use Closure;
use Google\Client;
use Google\Service\Exception as GoogleServiceException;
use Throwable;

abstract class BaseService
{
    protected OAuthService $oauth;

    public function __construct(?OAuthService $oauth = null)
    {
        $this->oauth = $oauth ?? new OAuthService(
            app(YoutubeClientFactory::class)->make()
        );
    }

    public static function withClient(Client $client): static
    {
        return new static(new OAuthService($client));
    }

    public function oauth(): OAuthService
    {
        return $this->oauth;
    }

    public function client(): Client
    {
        return $this->oauth->client();
    }

    /**
     * Wrap a Google API call: maps Google\Service\Exception -> YoutubeApiException
     * (and subclasses), preserving the previous chain.
     */
    protected function call(Closure $fn): mixed
    {
        try {
            return $fn();
        } catch (GoogleServiceException $e) {
            throw YoutubeApiException::fromGoogleException($e);
        } catch (Throwable $e) {
            throw new YoutubeApiException($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    /**
     * Apply the access token to the client before a call.
     * Returns refreshed token (if any) so callers can choose to persist it.
     *
     * @param array<string, mixed> $token
     * @return array<string, mixed>|null refreshed token, or null if no refresh occurred
     */
    protected function authorize(array $token): ?array
    {
        return $this->oauth->setAccessToken($token);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Services/BaseServiceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Services/BaseService.php tests/Unit/Services/BaseServiceTest.php
git commit -m "feat: BaseService with call wrapper and withClient factory"
```

### Task 7.1: `ChannelService::listById`

**Files:**
- Create: `src/Services/ChannelService.php` (start; methods added incrementally)
- Test: `tests/Unit/Services/ChannelServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services;

use Alchemyguy\YoutubeLaravelApi\Services\ChannelService;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Client;
use Google\Service\YouTube;
use Google\Service\YouTube\Resource\Channels;
use Mockery;

final class ChannelServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_list_by_id_calls_youtube_channels_list(): void
    {
        $channelsResource = Mockery::mock(Channels::class);
        $channelsResource->shouldReceive('listChannels')
            ->once()
            ->with('id,snippet', ['id' => 'UC1,UC2'])
            ->andReturn(['items' => [['id' => 'UC1'], ['id' => 'UC2']]]);

        $youtube = Mockery::mock(YouTube::class);
        $youtube->channels = $channelsResource;

        $client = Mockery::mock(Client::class);
        $svc = new class(new \Alchemyguy\YoutubeLaravelApi\Auth\OAuthService($client)) extends ChannelService {
            public ?YouTube $injected = null;
            protected function youtube(): YouTube { return $this->injected; }
        };
        $svc->injected = $youtube;

        $result = $svc->listById(['id' => 'UC1,UC2'], 'id,snippet');
        $this->assertSame('UC1', $result['items'][0]['id']);
    }
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Services/ChannelServiceTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

`src/Services/ChannelService.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services;

use Google\Service\YouTube;

class ChannelService extends BaseService
{
    protected function youtube(): YouTube
    {
        return new YouTube($this->client());
    }

    /**
     * Public, no-token lookup. $params accepts 'id' (comma-separated) or 'forUsername'.
     *
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function listById(array $params, string $part = 'id,snippet'): array
    {
        $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
        return $this->call(fn () => (array) $this->youtube()->channels->listChannels($part, $params));
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Services/ChannelServiceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Services/ChannelService.php tests/Unit/Services/ChannelServiceTest.php
git commit -m "feat: ChannelService::listById"
```

### Task 7.2: `ChannelService::getOwnChannel`

**Files:**
- Modify: `src/Services/ChannelService.php`
- Modify: `tests/Unit/Services/ChannelServiceTest.php`

- [ ] **Step 1: Add failing test**

Append to `ChannelServiceTest`:
```php
public function test_get_own_channel_authorizes_and_returns_first_item(): void
{
    $channelsResource = Mockery::mock(Channels::class);
    $channelsResource->shouldReceive('listChannels')
        ->once()
        ->with('snippet,contentDetails,statistics,brandingSettings', ['mine' => true])
        ->andReturn((object) ['items' => [(object) ['id' => 'UC-mine']]]);

    $youtube = Mockery::mock(YouTube::class);
    $youtube->channels = $channelsResource;

    $oauth = Mockery::mock(\Alchemyguy\YoutubeLaravelApi\Auth\OAuthService::class);
    $oauth->shouldReceive('setAccessToken')->once();

    $svc = new class($oauth) extends ChannelService {
        public ?YouTube $injected = null;
        protected function youtube(): YouTube { return $this->injected; }
    };
    $svc->injected = $youtube;

    $result = $svc->getOwnChannel(['access_token' => 'tok']);
    $this->assertSame('UC-mine', $result['id']);
}

public function test_get_own_channel_returns_null_when_no_items(): void
{
    $channelsResource = Mockery::mock(Channels::class);
    $channelsResource->shouldReceive('listChannels')->once()->andReturn((object) ['items' => []]);
    $youtube = Mockery::mock(YouTube::class);
    $youtube->channels = $channelsResource;

    $oauth = Mockery::mock(\Alchemyguy\YoutubeLaravelApi\Auth\OAuthService::class);
    $oauth->shouldReceive('setAccessToken');

    $svc = new class($oauth) extends ChannelService {
        public ?YouTube $injected = null;
        protected function youtube(): YouTube { return $this->injected; }
    };
    $svc->injected = $youtube;

    $this->assertNull($svc->getOwnChannel(['access_token' => 'tok']));
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Services/ChannelServiceTest.php`
Expected: FAIL on the new tests.

- [ ] **Step 3: Implement**

Add to `ChannelService`:
```php
/**
 * @param array<string, mixed> $token
 * @return array<string, mixed>|null
 */
public function getOwnChannel(array $token): ?array
{
    $this->authorize($token);

    return $this->call(function (): ?array {
        $response = $this->youtube()->channels->listChannels(
            'snippet,contentDetails,statistics,brandingSettings',
            ['mine' => true]
        );
        $decoded = json_decode(json_encode($response, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        return $decoded['items'][0] ?? null;
    });
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Services/ChannelServiceTest.php`
Expected: PASS (all three tests).

- [ ] **Step 5: Commit**

```bash
git add src/Services/ChannelService.php tests/Unit/Services/ChannelServiceTest.php
git commit -m "feat: ChannelService::getOwnChannel returns ?array"
```

### Task 7.3: `ChannelService::subscriptions` (paginated)

**Files:**
- Modify: `src/Services/ChannelService.php`
- Modify: `tests/Unit/Services/ChannelServiceTest.php`

- [ ] **Step 1: Add failing test**

```php
public function test_subscriptions_paginates_until_total_results(): void
{
    $page1 = (object) [
        'items' => [
            (object) ['snippet' => (object) ['resourceId' => (object) ['channelId' => 'A']]],
            (object) ['snippet' => (object) ['resourceId' => (object) ['channelId' => 'B']]],
        ],
        'nextPageToken' => 'tok2',
    ];
    $page2 = (object) [
        'items' => [
            (object) ['snippet' => (object) ['resourceId' => (object) ['channelId' => 'C']]],
        ],
    ];

    $subs = Mockery::mock(\Google\Service\YouTube\Resource\Subscriptions::class);
    $subs->shouldReceive('listSubscriptions')->twice()->andReturn($page1, $page2);
    $youtube = Mockery::mock(YouTube::class);
    $youtube->subscriptions = $subs;

    $svc = new class(new \Alchemyguy\YoutubeLaravelApi\Auth\OAuthService(Mockery::mock(Client::class))) extends ChannelService {
        public ?YouTube $injected = null;
        protected function youtube(): YouTube { return $this->injected; }
    };
    $svc->injected = $youtube;

    $result = $svc->subscriptions(['channelId' => 'UC1', 'totalResults' => 3]);
    $this->assertCount(3, $result);
    $this->assertSame(['A', 'B', 'C'], array_column($result, 'channelId'));
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Services/ChannelServiceTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

Add to `ChannelService`:
```php
/**
 * @param array<string, mixed> $params Required: 'channelId', 'totalResults'
 * @return array<int, array<string, mixed>>
 */
public function subscriptions(array $params, string $part = 'snippet'): array
{
    $channelId = (string) ($params['channelId'] ?? '');
    $totalResults = max(0, (int) ($params['totalResults'] ?? 0));
    $perPage = 50;

    return $this->call(function () use ($channelId, $totalResults, $perPage, $part): array {
        $youtube = $this->youtube();
        $maxPages = (int) ceil(max($totalResults, 1) / $perPage);
        $collected = [];
        $pageToken = null;

        for ($i = 0; $i < $maxPages; $i++) {
            $req = ['channelId' => $channelId, 'maxResults' => $perPage];
            if ($pageToken !== null) {
                $req['pageToken'] = $pageToken;
            }
            $resp = $youtube->subscriptions->listSubscriptions($part, $req);
            $decoded = json_decode(json_encode($resp, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
            foreach ($decoded['items'] ?? [] as $item) {
                $collected[] = $item['snippet']['resourceId'] ?? [];
                if (count($collected) >= $totalResults) {
                    return $collected;
                }
            }
            $pageToken = $decoded['nextPageToken'] ?? null;
            if ($pageToken === null) {
                break;
            }
        }
        return $collected;
    });
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Services/ChannelServiceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Services/ChannelService.php tests/Unit/Services/ChannelServiceTest.php
git commit -m "feat: ChannelService::subscriptions with pagination"
```

### Task 7.4: `ChannelService::subscribe` and `unsubscribe`

**Files:**
- Modify: `src/Services/ChannelService.php`
- Modify: `tests/Unit/Services/ChannelServiceTest.php`

- [ ] **Step 1: Add failing tests**

```php
public function test_subscribe_inserts_subscription(): void
{
    $subs = Mockery::mock(\Google\Service\YouTube\Resource\Subscriptions::class);
    $subs->shouldReceive('insert')->once()->withArgs(function (string $part, $resource) {
        return $part === 'snippet' && $resource instanceof \Google\Service\YouTube\Subscription;
    })->andReturn((object) ['id' => 'sub-1']);

    $youtube = Mockery::mock(YouTube::class);
    $youtube->subscriptions = $subs;

    $oauth = Mockery::mock(\Alchemyguy\YoutubeLaravelApi\Auth\OAuthService::class);
    $oauth->shouldReceive('setAccessToken')->once();

    $svc = new class($oauth) extends ChannelService {
        public ?YouTube $injected = null;
        protected function youtube(): YouTube { return $this->injected; }
    };
    $svc->injected = $youtube;

    $resp = $svc->subscribe(['access_token' => 'tok'], 'UC-target');
    $this->assertSame('sub-1', $resp['id']);
}

public function test_unsubscribe_deletes_subscription(): void
{
    $subs = Mockery::mock(\Google\Service\YouTube\Resource\Subscriptions::class);
    $subs->shouldReceive('delete')->once()->with('sub-id-1')->andReturn(null);

    $youtube = Mockery::mock(YouTube::class);
    $youtube->subscriptions = $subs;

    $oauth = Mockery::mock(\Alchemyguy\YoutubeLaravelApi\Auth\OAuthService::class);
    $oauth->shouldReceive('setAccessToken')->once();

    $svc = new class($oauth) extends ChannelService {
        public ?YouTube $injected = null;
        protected function youtube(): YouTube { return $this->injected; }
    };
    $svc->injected = $youtube;

    $svc->unsubscribe(['access_token' => 'tok'], 'sub-id-1');
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Services/ChannelServiceTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

Add to `ChannelService`:
```php
/**
 * Subscribe the authorized channel to the target channel.
 *
 * NOTE: YouTube heavily rate-limits subscription writes (anti-bot). Expect
 * frequent rejections for non-interactive automation.
 *
 * @param array<string, mixed> $token
 * @return array<string, mixed>
 */
public function subscribe(array $token, string $targetChannelId): array
{
    $this->authorize($token);
    return $this->call(function () use ($targetChannelId): array {
        $resource = new \Google\Service\YouTube\Subscription([
            'snippet' => [
                'resourceId' => [
                    'kind' => 'youtube#channel',
                    'channelId' => $targetChannelId,
                ],
            ],
        ]);
        $resp = $this->youtube()->subscriptions->insert('snippet', $resource);
        return (array) json_decode(json_encode($resp, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    });
}

/** @param array<string, mixed> $token */
public function unsubscribe(array $token, string $subscriptionId): void
{
    $this->authorize($token);
    $this->call(fn () => $this->youtube()->subscriptions->delete($subscriptionId));
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Services/ChannelServiceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Services/ChannelService.php tests/Unit/Services/ChannelServiceTest.php
git commit -m "feat: ChannelService::subscribe / unsubscribe"
```

### Task 7.5: `ChannelService::updateBranding`

**Files:**
- Modify: `src/Services/ChannelService.php`
- Modify: `tests/Unit/Services/ChannelServiceTest.php`

- [ ] **Step 1: Add failing test**

```php
public function test_update_branding_calls_channels_update_with_resource(): void
{
    $channels = Mockery::mock(\Google\Service\YouTube\Resource\Channels::class);
    $channels->shouldReceive('update')->once()->withArgs(function ($part, $resource, $params) {
        return $part === 'brandingSettings'
            && $resource instanceof \Google\Service\YouTube\Channel;
    })->andReturn((object) ['id' => 'UC1']);

    $youtube = Mockery::mock(YouTube::class);
    $youtube->channels = $channels;

    $oauth = Mockery::mock(\Alchemyguy\YoutubeLaravelApi\Auth\OAuthService::class);
    $oauth->shouldReceive('setAccessToken')->once();

    $svc = new class($oauth) extends ChannelService {
        public ?YouTube $injected = null;
        protected function youtube(): YouTube { return $this->injected; }
    };
    $svc->injected = $youtube;

    $props = new \Alchemyguy\YoutubeLaravelApi\DTOs\BrandingProperties(
        channelId: 'UC1',
        description: 'new desc',
        keywords: 'a,b',
    );
    $svc->updateBranding(['access_token' => 'tok'], $props);
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Services/ChannelServiceTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

Add to `ChannelService`:
```php
use Alchemyguy\YoutubeLaravelApi\DTOs\BrandingProperties;
use Alchemyguy\YoutubeLaravelApi\Support\ResourceBuilder;

/** @param array<string, mixed> $token */
public function updateBranding(array $token, BrandingProperties $properties): void
{
    $this->authorize($token);
    $this->call(function () use ($properties): void {
        $resource = new \Google\Service\YouTube\Channel(
            ResourceBuilder::fromProperties($properties->toDottedArray())
        );
        $this->youtube()->channels->update('brandingSettings', $resource);
    });
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Services/ChannelServiceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Services/ChannelService.php tests/Unit/Services/ChannelServiceTest.php
git commit -m "feat: ChannelService::updateBranding via DTO"
```

## Phase 8: Video service

### Task 8.1: `VideoService::listById` + `search`

**Files:**
- Create: `src/Services/VideoService.php`
- Test: `tests/Unit/Services/VideoServiceTest.php`

- [ ] **Step 1: Write the failing tests**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\Services\VideoService;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Client;
use Google\Service\YouTube;
use Google\Service\YouTube\Resource\Videos;
use Google\Service\YouTube\Resource\Search;
use Mockery;

final class VideoServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_list_by_id_returns_videos(): void
    {
        $videos = Mockery::mock(Videos::class);
        $videos->shouldReceive('listVideos')->once()
            ->with('snippet,contentDetails,id,statistics', ['id' => 'vid1'])
            ->andReturn(['items' => [['id' => 'vid1']]]);

        $youtube = Mockery::mock(YouTube::class);
        $youtube->videos = $videos;

        $svc = new class(new OAuthService(Mockery::mock(Client::class))) extends VideoService {
            public ?YouTube $injected = null;
            protected function youtube(): YouTube { return $this->injected; }
        };
        $svc->injected = $youtube;

        $r = $svc->listById(['id' => 'vid1']);
        $this->assertSame('vid1', $r['items'][0]['id']);
    }

    public function test_search_filters_empty_params(): void
    {
        $search = Mockery::mock(Search::class);
        $search->shouldReceive('listSearch')->once()
            ->with('snippet,id', ['q' => 'cats'])
            ->andReturn(['items' => []]);

        $youtube = Mockery::mock(YouTube::class);
        $youtube->search = $search;

        $svc = new class(new OAuthService(Mockery::mock(Client::class))) extends VideoService {
            public ?YouTube $injected = null;
            protected function youtube(): YouTube { return $this->injected; }
        };
        $svc->injected = $youtube;

        $svc->search(['q' => 'cats', 'pageToken' => '']);
    }
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Services/VideoServiceTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement (initial)**

`src/Services/VideoService.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services;

use Google\Service\YouTube;

class VideoService extends BaseService
{
    protected function youtube(): YouTube
    {
        return new YouTube($this->client());
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function listById(array $params, string $part = 'snippet,contentDetails,id,statistics'): array
    {
        $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
        return $this->call(fn () => (array) $this->youtube()->videos->listVideos($part, $params));
    }

    /**
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     */
    public function search(array $params, string $part = 'snippet,id'): array
    {
        $params = array_filter($params, static fn ($v) => $v !== null && $v !== '');
        return $this->call(fn () => (array) $this->youtube()->search->listSearch($part, $params));
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Services/VideoServiceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Services/VideoService.php tests/Unit/Services/VideoServiceTest.php
git commit -m "feat: VideoService::listById and search"
```

### Task 8.2: `VideoService::delete` + `rate` (regression for bug #2)

**Files:**
- Modify: `src/Services/VideoService.php`
- Modify: `tests/Unit/Services/VideoServiceTest.php`

- [ ] **Step 1: Add failing tests**

```php
public function test_delete_video_authorizes_and_deletes(): void
{
    $videos = Mockery::mock(Videos::class);
    $videos->shouldReceive('delete')->once()->with('vid1')->andReturn(null);
    $youtube = Mockery::mock(YouTube::class);
    $youtube->videos = $videos;

    $oauth = Mockery::mock(OAuthService::class);
    $oauth->shouldReceive('setAccessToken')->once();

    $svc = new class($oauth) extends VideoService {
        public ?YouTube $injected = null;
        protected function youtube(): YouTube { return $this->injected; }
    };
    $svc->injected = $youtube;

    $svc->delete(['access_token' => 'tok'], 'vid1');
}

/** @bug regression for VideoService::videosRate (Section 6, bug 2) */
public function test_rate_calls_videos_rate_with_enum_value(): void
{
    $videos = Mockery::mock(Videos::class);
    $videos->shouldReceive('rate')->once()->with('vid1', 'like')->andReturn(null);
    $youtube = Mockery::mock(YouTube::class);
    $youtube->videos = $videos;

    $oauth = Mockery::mock(OAuthService::class);
    $oauth->shouldReceive('setAccessToken')->once();

    $svc = new class($oauth) extends VideoService {
        public ?YouTube $injected = null;
        protected function youtube(): YouTube { return $this->injected; }
    };
    $svc->injected = $youtube;

    $svc->rate(['access_token' => 'tok'], 'vid1', \Alchemyguy\YoutubeLaravelApi\Enums\Rating::Like);
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Services/VideoServiceTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

Add to `VideoService`:
```php
use Alchemyguy\YoutubeLaravelApi\Enums\Rating;

/** @param array<string, mixed> $token */
public function delete(array $token, string $videoId): void
{
    $this->authorize($token);
    $this->call(fn () => $this->youtube()->videos->delete($videoId));
}

/** @param array<string, mixed> $token */
public function rate(array $token, string $videoId, Rating $rating): void
{
    $this->authorize($token);
    $this->call(fn () => $this->youtube()->videos->rate($videoId, $rating->value));
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Services/VideoServiceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Services/VideoService.php tests/Unit/Services/VideoServiceTest.php
git commit -m "feat: VideoService::delete and rate (fixes 1.x bug #2)"
```

### Task 8.3: `VideoService::upload` (chunked, with try/finally — regression for bug #7)

**Files:**
- Modify: `src/Services/VideoService.php`
- Modify: `tests/Unit/Services/VideoServiceTest.php`
- Test fixture: `tests/Fixtures/test_video.txt` (placeholder file)

- [ ] **Step 1: Add fixture**

```bash
echo "test video content" > tests/Fixtures/test_video.txt
```

- [ ] **Step 2: Add failing test**

```php
public function test_upload_resets_defer_in_finally_even_on_exception(): void
{
    $client = Mockery::mock(Client::class);
    $client->shouldReceive('setDefer')->ordered()->once()->with(true);
    $client->shouldReceive('setDefer')->ordered()->once()->with(false); // must be called even on failure
    $client->shouldReceive('isAccessTokenExpired')->andReturn(false);
    $client->shouldReceive('setAccessToken')->once();

    $videos = Mockery::mock(Videos::class);
    $videos->shouldReceive('insert')->andThrow(new \Google\Service\Exception('boom'));
    $youtube = Mockery::mock(YouTube::class);
    $youtube->videos = $videos;

    $svc = new class(new OAuthService($client)) extends VideoService {
        public ?YouTube $injected = null;
        protected function youtube(): YouTube { return $this->injected; }
    };
    $svc->injected = $youtube;

    $this->expectException(\Alchemyguy\YoutubeLaravelApi\Exceptions\YoutubeApiException::class);
    $svc->upload(
        ['access_token' => 'tok'],
        __DIR__ . '/../../Fixtures/test_video.txt',
        new \Alchemyguy\YoutubeLaravelApi\DTOs\VideoUploadData(
            'title', 'desc', '22',
            \Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus::Public
        ),
    );
}
```

- [ ] **Step 3: Run test**

Run: `vendor/bin/pest tests/Unit/Services/VideoServiceTest.php`
Expected: FAIL.

- [ ] **Step 4: Implement**

Add to `VideoService`:
```php
use Alchemyguy\YoutubeLaravelApi\DTOs\VideoUploadData;
use Alchemyguy\YoutubeLaravelApi\Exceptions\ConfigurationException;
use Google\Http\MediaFileUpload;
use Google\Service\YouTube\Video;
use Google\Service\YouTube\VideoSnippet;
use Google\Service\YouTube\VideoStatus;

/**
 * Upload a video using a resumable, chunked upload.
 *
 * @param array<string, mixed> $token
 * @return array<string, mixed>
 */
public function upload(array $token, string $videoPath, VideoUploadData $data): array
{
    if (!is_file($videoPath)) {
        throw new ConfigurationException("Video file not found: {$videoPath}");
    }

    $this->authorize($token);
    $client = $this->client();

    $snippet = new VideoSnippet();
    $snippet->setTitle($data->title);
    $snippet->setDescription($data->description);
    $snippet->setTags($data->tags);
    $snippet->setCategoryId($data->categoryId);

    $status = new VideoStatus();
    $status->setPrivacyStatus($data->privacyStatus->value);

    $video = new Video();
    $video->setSnippet($snippet);
    $video->setStatus($status);

    $client->setDefer(true);
    try {
        return $this->call(function () use ($client, $videoPath, $data, $video): array {
            $insertRequest = $this->youtube()->videos->insert('status,snippet', $video);
            $media = new MediaFileUpload(
                $client,
                $insertRequest,
                'video/*',
                null,
                true,
                $data->chunkSizeBytes
            );
            $media->setFileSize((int) filesize($videoPath));

            $handle = fopen($videoPath, 'rb');
            if ($handle === false) {
                throw new ConfigurationException("Cannot open video file: {$videoPath}");
            }
            try {
                $status = false;
                while (!$status && !feof($handle)) {
                    $chunk = fread($handle, $data->chunkSizeBytes);
                    if ($chunk === false) {
                        throw new ConfigurationException('Failed to read video chunk');
                    }
                    $status = $media->nextChunk($chunk);
                }
            } finally {
                fclose($handle);
            }
            return is_array($status) ? $status : (array) json_decode(json_encode($status, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        });
    } finally {
        $client->setDefer(false);
    }
}
```

- [ ] **Step 5: Run tests**

Run: `vendor/bin/pest tests/Unit/Services/VideoServiceTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Services/VideoService.php tests/Unit/Services/VideoServiceTest.php tests/Fixtures/test_video.txt
git commit -m "feat: VideoService::upload with try/finally setDefer (fixes bug #7)"
```

---

## Phase 9: Live streaming

### Task 9.1: `ThumbnailUploader` (mime detection — regression for bug #6, try/finally — regression for bug #7)

**Files:**
- Create: `src/Services/LiveStream/ThumbnailUploader.php`
- Test: `tests/Unit/Services/LiveStream/ThumbnailUploaderTest.php`

- [ ] **Step 1: Add fixture**

```bash
# Create a 1x1 PNG as fixture
printf '\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15\xc4\x89\x00\x00\x00\x0dIDATx\x9cc\xf8\xff\xff?\x00\x05\xfe\x02\xfe\xa7\xea\x83\x37\x00\x00\x00\x00IEND\xaeB`\x82' > tests/Fixtures/images/test_thumbnail.png
```

- [ ] **Step 2: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services\LiveStream;

use Alchemyguy\YoutubeLaravelApi\Exceptions\ConfigurationException;
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\ThumbnailUploader;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Client;
use Google\Service\YouTube\Resource\Thumbnails;
use Mockery;

final class ThumbnailUploaderTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_throws_when_thumbnail_file_missing(): void
    {
        $this->expectException(ConfigurationException::class);
        (new ThumbnailUploader(Mockery::mock(Client::class), Mockery::mock(Thumbnails::class)))
            ->upload('/nonexistent.png', 'video-1');
    }

    public function test_rejects_unsupported_mime_type(): void
    {
        $tmp = tempnam(sys_get_temp_dir(), 'gif');
        file_put_contents($tmp, "GIF89a\x01\x00\x01\x00\x00\xff\xff\xff!\xf9\x04\x00\x00\x00\x00\x00,\x00\x00\x00\x00\x01\x00\x01\x00\x00\x02\x02D\x01\x00;");

        try {
            $this->expectException(ConfigurationException::class);
            $this->expectExceptionMessage('image/gif');
            (new ThumbnailUploader(Mockery::mock(Client::class), Mockery::mock(Thumbnails::class)))
                ->upload($tmp, 'video-1');
        } finally {
            unlink($tmp);
        }
    }

    public function test_resets_defer_even_when_upload_fails(): void
    {
        $client = Mockery::mock(Client::class);
        $client->shouldReceive('setDefer')->ordered()->once()->with(true);
        $client->shouldReceive('setDefer')->ordered()->once()->with(false);

        $thumbs = Mockery::mock(Thumbnails::class);
        $thumbs->shouldReceive('set')->andThrow(new \RuntimeException('boom'));

        $this->expectException(\RuntimeException::class);
        try {
            (new ThumbnailUploader($client, $thumbs))
                ->upload(__DIR__ . '/../../../Fixtures/images/test_thumbnail.png', 'video-1');
        } finally {
            // assertions on Mockery expectations confirm setDefer(false) was called
        }
    }
}
```

- [ ] **Step 3: Run test**

Run: `vendor/bin/pest tests/Unit/Services/LiveStream/ThumbnailUploaderTest.php`
Expected: FAIL.

- [ ] **Step 4: Implement**

`src/Services/LiveStream/ThumbnailUploader.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services\LiveStream;

use Alchemyguy\YoutubeLaravelApi\Exceptions\ConfigurationException;
use Google\Client;
use Google\Http\MediaFileUpload;
use Google\Service\YouTube\Resource\Thumbnails;

final class ThumbnailUploader
{
    private const ALLOWED_MIME = ['image/jpeg', 'image/png'];
    private const CHUNK_SIZE_BYTES = 1048576;

    public function __construct(
        private readonly Client $client,
        private readonly Thumbnails $thumbnails,
    ) {}

    public function upload(string $path, string $videoId): string
    {
        if (!is_file($path)) {
            throw new ConfigurationException("Thumbnail file not found: {$path}");
        }
        $mime = (string) (mime_content_type($path) ?: 'application/octet-stream');
        if (!in_array($mime, self::ALLOWED_MIME, true)) {
            throw new ConfigurationException(
                "Unsupported thumbnail MIME type '{$mime}'. Allowed: " . implode(', ', self::ALLOWED_MIME)
            );
        }

        $this->client->setDefer(true);
        try {
            $request = $this->thumbnails->set($videoId);
            $media = new MediaFileUpload(
                $this->client,
                $request,
                $mime,
                null,
                true,
                self::CHUNK_SIZE_BYTES
            );
            $media->setFileSize((int) filesize($path));

            $handle = fopen($path, 'rb');
            if ($handle === false) {
                throw new ConfigurationException("Cannot open thumbnail file: {$path}");
            }
            try {
                $status = false;
                while (!$status && !feof($handle)) {
                    $chunk = fread($handle, self::CHUNK_SIZE_BYTES);
                    if ($chunk === false) {
                        throw new ConfigurationException('Failed to read thumbnail chunk');
                    }
                    $status = $media->nextChunk($chunk);
                }
            } finally {
                fclose($handle);
            }

            return (string) ($status['items'][0]['default']['url'] ?? '');
        } finally {
            $this->client->setDefer(false);
        }
    }
}
```

- [ ] **Step 5: Run tests**

Run: `vendor/bin/pest tests/Unit/Services/LiveStream/ThumbnailUploaderTest.php`
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add src/Services/LiveStream/ThumbnailUploader.php tests/Unit/Services/LiveStream/ThumbnailUploaderTest.php tests/Fixtures/images/test_thumbnail.png
git commit -m "feat: ThumbnailUploader with mime detection and try/finally (fixes bugs #6 and #7)"
```

### Task 9.2: `BroadcastManager::insert` and `update`

**Files:**
- Create: `src/Services/LiveStream/BroadcastManager.php`
- Test: `tests/Unit/Services/LiveStream/BroadcastManagerTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services\LiveStream;

use Alchemyguy\YoutubeLaravelApi\DTOs\BroadcastData;
use Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus;
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\BroadcastManager;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use DateTimeImmutable;
use Google\Service\YouTube\LiveBroadcast;
use Google\Service\YouTube\Resource\LiveBroadcasts;
use Mockery;

final class BroadcastManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_insert_calls_live_broadcasts_insert(): void
    {
        $broadcasts = Mockery::mock(LiveBroadcasts::class);
        $broadcasts->shouldReceive('insert')->once()->withArgs(function ($part, $resource, $params) {
            return $part === 'snippet,status'
                && $resource instanceof LiveBroadcast;
        })->andReturn((object) ['id' => 'evt-1']);

        $data = new BroadcastData(
            title: 'Live Title',
            description: 'desc',
            scheduledStartTime: new DateTimeImmutable('+1 hour'),
            privacyStatus: PrivacyStatus::Public,
        );

        $resp = (new BroadcastManager($broadcasts))->insert($data);
        $this->assertSame('evt-1', $resp['id']);
    }
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Services/LiveStream/BroadcastManagerTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

`src/Services/LiveStream/BroadcastManager.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services\LiveStream;

use Alchemyguy\YoutubeLaravelApi\DTOs\BroadcastData;
use Alchemyguy\YoutubeLaravelApi\Enums\BroadcastStatus;
use Alchemyguy\YoutubeLaravelApi\Exceptions\ConfigurationException;
use Google\Service\YouTube\LiveBroadcast;
use Google\Service\YouTube\LiveBroadcastSnippet;
use Google\Service\YouTube\LiveBroadcastStatus;
use Google\Service\YouTube\Resource\LiveBroadcasts;

final class BroadcastManager
{
    public function __construct(private readonly LiveBroadcasts $broadcasts) {}

    /** @return array<string, mixed> */
    public function insert(BroadcastData $data): array
    {
        $broadcast = $this->buildResource($data);
        $resp = $this->broadcasts->insert('snippet,status', $broadcast);
        return $this->decode($resp);
    }

    /** @return array<string, mixed> */
    public function update(string $broadcastId, BroadcastData $data): array
    {
        $broadcast = $this->buildResource($data);
        $broadcast->setId($broadcastId);
        $resp = $this->broadcasts->update('snippet,status', $broadcast);
        return $this->decode($resp);
    }

    /** Bug #1 fix: was inverted. Now correctly errors when token is empty (callers handle). */
    public function transition(string $broadcastId, BroadcastStatus $status): array
    {
        if ($broadcastId === '') {
            throw new ConfigurationException('broadcastId cannot be empty.');
        }
        $resp = $this->broadcasts->transition(
            $status->value,
            $broadcastId,
            'status,id,snippet'
        );
        return $this->decode($resp);
    }

    public function delete(string $broadcastId): void
    {
        $this->broadcasts->delete($broadcastId);
    }

    private function buildResource(BroadcastData $data): LiveBroadcast
    {
        $snippet = new LiveBroadcastSnippet();
        $snippet->setTitle($data->title);
        $snippet->setDescription($data->description);
        $snippet->setScheduledStartTime($data->scheduledStartTime->format(DATE_ATOM));
        if ($data->scheduledEndTime !== null) {
            $snippet->setScheduledEndTime($data->scheduledEndTime->format(DATE_ATOM));
        }

        $status = new LiveBroadcastStatus();
        $status->setPrivacyStatus($data->privacyStatus->value);

        $broadcast = new LiveBroadcast();
        $broadcast->setSnippet($snippet);
        $broadcast->setStatus($status);
        $broadcast->setKind('youtube#liveBroadcast');

        return $broadcast;
    }

    /**
     * @param mixed $resp
     * @return array<string, mixed>
     */
    private function decode($resp): array
    {
        if (is_array($resp)) {
            return $resp;
        }
        return (array) json_decode(json_encode($resp, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Services/LiveStream/BroadcastManagerTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Services/LiveStream/BroadcastManager.php tests/Unit/Services/LiveStream/BroadcastManagerTest.php
git commit -m "feat: BroadcastManager (insert/update/transition/delete)"
```

### Task 9.3: `BroadcastManager::transition` regression for bug #1

**Files:**
- Modify: `tests/Unit/Services/LiveStream/BroadcastManagerTest.php`

- [ ] **Step 1: Add the regression test (bug #1 — inverted check)**

Append to `BroadcastManagerTest`:
```php
/** @bug regression for LiveStreamService::transitionEvent (Section 6, bug 1) */
public function test_transition_actually_transitions_when_token_is_present(): void
{
    $broadcasts = Mockery::mock(LiveBroadcasts::class);
    $broadcasts->shouldReceive('transition')
        ->once()
        ->with('live', 'evt-1', 'status,id,snippet')
        ->andReturn((object) ['lifeCycleStatus' => 'live']);

    $resp = (new BroadcastManager($broadcasts))->transition(
        'evt-1',
        \Alchemyguy\YoutubeLaravelApi\Enums\BroadcastStatus::Live
    );
    $this->assertSame('live', $resp['lifeCycleStatus']);
}

public function test_transition_throws_on_empty_broadcast_id(): void
{
    $this->expectException(\Alchemyguy\YoutubeLaravelApi\Exceptions\ConfigurationException::class);
    (new BroadcastManager(Mockery::mock(LiveBroadcasts::class)))
        ->transition('', \Alchemyguy\YoutubeLaravelApi\Enums\BroadcastStatus::Live);
}

public function test_delete_calls_live_broadcasts_delete(): void
{
    $broadcasts = Mockery::mock(LiveBroadcasts::class);
    $broadcasts->shouldReceive('delete')->once()->with('evt-1');
    (new BroadcastManager($broadcasts))->delete('evt-1');
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Services/LiveStream/BroadcastManagerTest.php`
Expected: PASS (manager implementation already covers).

- [ ] **Step 3: Commit**

```bash
git add tests/Unit/Services/LiveStream/BroadcastManagerTest.php
git commit -m "test: regression coverage for bug #1 (transition guard)"
```

### Task 9.4: `StreamManager` (insert + bind)

**Files:**
- Create: `src/Services/LiveStream/StreamManager.php`
- Test: `tests/Unit/Services/LiveStream/StreamManagerTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services\LiveStream;

use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\StreamManager;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Service\YouTube\LiveStream;
use Google\Service\YouTube\Resource\LiveBroadcasts;
use Google\Service\YouTube\Resource\LiveStreams;
use Mockery;

final class StreamManagerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_insert_creates_stream_with_rtmp_720p(): void
    {
        $streams = Mockery::mock(LiveStreams::class);
        $streams->shouldReceive('insert')->once()->withArgs(function ($part, $resource, $params) {
            return $part === 'snippet,cdn' && $resource instanceof LiveStream;
        })->andReturn((object) ['id' => 'stream-1']);

        $broadcasts = Mockery::mock(LiveBroadcasts::class);

        $r = (new StreamManager($streams, $broadcasts))->insert('Title');
        $this->assertSame('stream-1', $r['id']);
    }

    public function test_bind_links_broadcast_to_stream(): void
    {
        $streams = Mockery::mock(LiveStreams::class);
        $broadcasts = Mockery::mock(LiveBroadcasts::class);
        $broadcasts->shouldReceive('bind')->once()
            ->with('evt-1', 'id,contentDetails', ['streamId' => 'stream-1'])
            ->andReturn((object) ['id' => 'evt-1']);

        $r = (new StreamManager($streams, $broadcasts))->bind('evt-1', 'stream-1');
        $this->assertSame('evt-1', $r['id']);
    }
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Services/LiveStream/StreamManagerTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

`src/Services/LiveStream/StreamManager.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services\LiveStream;

use Google\Service\YouTube\CdnSettings;
use Google\Service\YouTube\LiveStream;
use Google\Service\YouTube\LiveStreamSnippet;
use Google\Service\YouTube\Resource\LiveBroadcasts;
use Google\Service\YouTube\Resource\LiveStreams;

final class StreamManager
{
    public function __construct(
        private readonly LiveStreams $streams,
        private readonly LiveBroadcasts $broadcasts,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function insert(string $title, string $format = '720p', string $ingestionType = 'rtmp'): array
    {
        $snippet = new LiveStreamSnippet();
        $snippet->setTitle($title);

        $cdn = new CdnSettings();
        $cdn->setFormat($format);
        $cdn->setIngestionType($ingestionType);

        $stream = new LiveStream();
        $stream->setSnippet($snippet);
        $stream->setCdn($cdn);
        $stream->setKind('youtube#liveStream');

        $resp = $this->streams->insert('snippet,cdn', $stream);
        return $this->decode($resp);
    }

    /**
     * @return array<string, mixed>
     */
    public function bind(string $broadcastId, string $streamId): array
    {
        $resp = $this->broadcasts->bind(
            $broadcastId,
            'id,contentDetails',
            ['streamId' => $streamId]
        );
        return $this->decode($resp);
    }

    /**
     * @param mixed $resp
     * @return array<string, mixed>
     */
    private function decode($resp): array
    {
        if (is_array($resp)) {
            return $resp;
        }
        return (array) json_decode(json_encode($resp, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Services/LiveStream/StreamManagerTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Services/LiveStream/StreamManager.php tests/Unit/Services/LiveStream/StreamManagerTest.php
git commit -m "feat: StreamManager (insert/bind)"
```

### Task 9.5: `LiveStreamService` (orchestrator)

**Files:**
- Create: `src/Services/LiveStream/LiveStreamService.php`
- Test: `tests/Unit/Services/LiveStream/LiveStreamServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services\LiveStream;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\DTOs\BroadcastData;
use Alchemyguy\YoutubeLaravelApi\Enums\BroadcastStatus;
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\BroadcastManager;
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\LiveStreamService;
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\StreamManager;
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\ThumbnailUploader;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use DateTimeImmutable;
use Google\Client;
use Google\Service\YouTube;
use Google\Service\YouTube\Resource\Videos;
use Mockery;

final class LiveStreamServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_broadcast_orchestrates_insert_metadata_stream_bind(): void
    {
        $broadcasts = Mockery::mock(BroadcastManager::class);
        $broadcasts->shouldReceive('insert')->once()->andReturn(['id' => 'evt-1']);

        $streams = Mockery::mock(StreamManager::class);
        $streams->shouldReceive('insert')->once()->andReturn(['id' => 'stream-1', 'cdn' => ['ingestionInfo' => ['ingestionAddress' => 'rtmp://x', 'streamName' => 'k']]]);
        $streams->shouldReceive('bind')->once()->with('evt-1', 'stream-1')->andReturn(['id' => 'evt-1']);

        $videos = Mockery::mock(Videos::class);
        $videos->shouldReceive('listVideos')->once()->andReturn(['items' => [['snippet' => ['tags' => []]]]]);
        $videos->shouldReceive('update')->once()->andReturn(['id' => 'evt-1']);
        $youtube = Mockery::mock(YouTube::class);
        $youtube->videos = $videos;

        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('setAccessToken')->once();
        $oauth->shouldReceive('client')->andReturn(Mockery::mock(Client::class));

        $svc = new LiveStreamService(
            oauth: $oauth,
            broadcasts: $broadcasts,
            streams: $streams,
            thumbnails: Mockery::mock(ThumbnailUploader::class),
            youtube: $youtube,
            languages: ['English' => 'en'],
        );

        $data = new BroadcastData(
            title: 'T',
            description: 'D',
            scheduledStartTime: new DateTimeImmutable('+1 hour'),
        );
        $resp = $svc->broadcast(['access_token' => 'tok'], $data);

        $this->assertSame('evt-1', $resp['broadcast']['id']);
        $this->assertSame('stream-1', $resp['stream']['id']);
        $this->assertSame('rtmp://x', $resp['stream']['cdn']['ingestionInfo']['ingestionAddress']);
    }

    public function test_transition_delegates_to_broadcast_manager(): void
    {
        $broadcasts = Mockery::mock(BroadcastManager::class);
        $broadcasts->shouldReceive('transition')->once()->with('evt-1', BroadcastStatus::Live)
            ->andReturn(['lifeCycleStatus' => 'live']);

        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('setAccessToken')->once();

        $svc = new LiveStreamService(
            oauth: $oauth,
            broadcasts: $broadcasts,
            streams: Mockery::mock(StreamManager::class),
            thumbnails: Mockery::mock(ThumbnailUploader::class),
            youtube: Mockery::mock(YouTube::class),
            languages: [],
        );

        $r = $svc->transition(['access_token' => 'tok'], 'evt-1', BroadcastStatus::Live);
        $this->assertSame('live', $r['lifeCycleStatus']);
    }
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Services/LiveStream/LiveStreamServiceTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

`src/Services/LiveStream/LiveStreamService.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services\LiveStream;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\DTOs\BroadcastData;
use Alchemyguy\YoutubeLaravelApi\Enums\BroadcastStatus;
use Alchemyguy\YoutubeLaravelApi\Exceptions\YoutubeApiException;
use Alchemyguy\YoutubeLaravelApi\Services\BaseService;
use Alchemyguy\YoutubeLaravelApi\Support\YoutubeClientFactory;
use Google\Client;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\YouTube;
use Throwable;

class LiveStreamService extends BaseService
{
    private BroadcastManager $broadcasts;
    private StreamManager $streams;
    private ThumbnailUploader $thumbnails;
    private YouTube $youtube;
    /** @var array<string, string> */
    private array $languages;

    /**
     * @param array<string, string> $languages
     */
    public function __construct(
        ?OAuthService $oauth = null,
        ?BroadcastManager $broadcasts = null,
        ?StreamManager $streams = null,
        ?ThumbnailUploader $thumbnails = null,
        ?YouTube $youtube = null,
        ?array $languages = null,
    ) {
        parent::__construct($oauth);
        $client = $this->client();
        $this->youtube = $youtube ?? new YouTube($client);
        $this->broadcasts = $broadcasts ?? new BroadcastManager($this->youtube->liveBroadcasts);
        $this->streams = $streams ?? new StreamManager($this->youtube->liveStreams, $this->youtube->liveBroadcasts);
        $this->thumbnails = $thumbnails ?? new ThumbnailUploader($client, $this->youtube->thumbnails);
        $this->languages = $languages ?? (array) config('youtube.languages', []);
    }

    /**
     * Create + configure + bind a broadcast and stream.
     *
     * @param array<string, mixed> $token
     * @return array{broadcast: array<string, mixed>, stream: array<string, mixed>, binding: array<string, mixed>}
     */
    public function broadcast(array $token, BroadcastData $data): array
    {
        $this->authorize($token);

        return $this->call(function () use ($data): array {
            $broadcast = $this->broadcasts->insert($data);
            $broadcastId = (string) ($broadcast['id'] ?? '');
            if ($broadcastId === '') {
                throw new YoutubeApiException('Broadcast insert returned no id.');
            }

            if ($data->thumbnailPath !== null) {
                $this->thumbnails->upload($data->thumbnailPath, $broadcastId);
            }

            $this->updateVideoMetadata($broadcastId, $data);

            $stream = $this->streams->insert($data->title);
            $streamId = (string) ($stream['id'] ?? '');
            if ($streamId === '') {
                throw new YoutubeApiException('Stream insert returned no id.');
            }

            $binding = $this->streams->bind($broadcastId, $streamId);

            return [
                'broadcast' => $broadcast,
                'stream' => $stream,
                'binding' => $binding,
            ];
        });
    }

    /**
     * @param array<string, mixed> $token
     * @return array{broadcast: array<string, mixed>, stream: array<string, mixed>, binding: array<string, mixed>}
     */
    public function updateBroadcast(array $token, string $broadcastId, BroadcastData $data): array
    {
        $this->authorize($token);
        return $this->call(function () use ($broadcastId, $data): array {
            $broadcast = $this->broadcasts->update($broadcastId, $data);

            if ($data->thumbnailPath !== null) {
                $this->thumbnails->upload($data->thumbnailPath, $broadcastId);
            }
            $this->updateVideoMetadata($broadcastId, $data);

            $stream = $this->streams->insert($data->title);
            $streamId = (string) ($stream['id'] ?? '');
            $binding = $this->streams->bind($broadcastId, $streamId);

            return [
                'broadcast' => $broadcast,
                'stream' => $stream,
                'binding' => $binding,
            ];
        });
    }

    /**
     * @param array<string, mixed> $token
     * @return array<string, mixed>
     */
    public function transition(array $token, string $broadcastId, BroadcastStatus $status): array
    {
        $this->authorize($token);
        return $this->call(fn () => $this->broadcasts->transition($broadcastId, $status));
    }

    /** @param array<string, mixed> $token */
    public function delete(array $token, string $broadcastId): void
    {
        $this->authorize($token);
        $this->call(fn () => $this->broadcasts->delete($broadcastId));
    }

    /**
     * Read-back-after-write with bounded retry to handle eventual consistency.
     * Bug #15 fix.
     */
    private function updateVideoMetadata(string $videoId, BroadcastData $data): void
    {
        $videoSnippet = $this->fetchVideoSnippetWithRetry($videoId);
        $videoSnippet['tags'] = $data->tags;
        $lang = $this->languages[$data->languageName] ?? 'en';
        $videoSnippet['defaultAudioLanguage'] = $lang;
        $videoSnippet['defaultLanguage'] = $lang;
        $videoSnippet['title'] = $data->title;
        $videoSnippet['description'] = $data->description;

        $this->youtube->videos->update('snippet', new \Google\Service\YouTube\Video([
            'id' => $videoId,
            'snippet' => $videoSnippet,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchVideoSnippetWithRetry(string $videoId): array
    {
        $attempts = 0;
        $delayMs = 500;
        while ($attempts < 3) {
            $resp = $this->youtube->videos->listVideos('snippet', ['id' => $videoId]);
            $decoded = is_array($resp)
                ? $resp
                : (array) json_decode(json_encode($resp, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
            $items = $decoded['items'] ?? [];
            if (!empty($items)) {
                return (array) ($items[0]['snippet'] ?? []);
            }
            $attempts++;
            usleep($delayMs * 1000);
        }
        throw new YoutubeApiException("Video {$videoId} not visible after retries.");
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Services/LiveStream/LiveStreamServiceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Services/LiveStream/LiveStreamService.php tests/Unit/Services/LiveStream/LiveStreamServiceTest.php
git commit -m "feat: LiveStreamService orchestrator (broadcast/update/transition/delete)"
```

## Phase 10: AuthenticateService

### Task 10.1: `AuthenticateService::authenticateWithCode` (with read-only live-streaming probe — bug #4 + replaces `liveStreamTest`)

**Files:**
- Create: `src/Services/AuthenticateService.php`
- Test: `tests/Unit/Services/AuthenticateServiceTest.php`

- [ ] **Step 1: Write the failing test**

```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Tests\Unit\Services;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\Services\AuthenticateService;
use Alchemyguy\YoutubeLaravelApi\Tests\TestCase;
use Google\Client;
use Google\Service\YouTube;
use Google\Service\YouTube\Resource\Channels;
use Google\Service\YouTube\Resource\LiveBroadcasts;
use Mockery;

final class AuthenticateServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_authenticate_with_code_returns_token_channel_and_live_streaming_status(): void
    {
        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('exchangeCode')->once()->with('code123')
            ->andReturn(['access_token' => 'tok', 'refresh_token' => 'rt']);
        $oauth->shouldReceive('setAccessToken')->once();
        $oauth->shouldReceive('client')->andReturn(Mockery::mock(Client::class));

        $channels = Mockery::mock(Channels::class);
        $channels->shouldReceive('listChannels')->once()->with('snippet', ['mine' => true])
            ->andReturn((object) ['items' => [(object) ['id' => 'UC-mine']]]);

        $broadcasts = Mockery::mock(LiveBroadcasts::class);
        $broadcasts->shouldReceive('listLiveBroadcasts')->once()
            ->with('id', ['mine' => true, 'maxResults' => 1])
            ->andReturn((object) ['items' => []]);

        $youtube = Mockery::mock(YouTube::class);
        $youtube->channels = $channels;
        $youtube->liveBroadcasts = $broadcasts;

        $svc = new AuthenticateService(oauth: $oauth, youtube: $youtube);
        $r = $svc->authenticateWithCode('code123');

        $this->assertSame('tok', $r['token']['access_token']);
        $this->assertSame('UC-mine', $r['channel']['id']);
        $this->assertTrue($r['liveStreamingEnabled']);
    }

    public function test_live_streaming_enabled_false_when_google_returns_specific_error(): void
    {
        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('exchangeCode')->andReturn(['access_token' => 't']);
        $oauth->shouldReceive('setAccessToken');
        $oauth->shouldReceive('client')->andReturn(Mockery::mock(Client::class));

        $channels = Mockery::mock(Channels::class);
        $channels->shouldReceive('listChannels')->andReturn((object) ['items' => [(object) ['id' => 'UC1']]]);

        $broadcasts = Mockery::mock(LiveBroadcasts::class);
        $broadcasts->shouldReceive('listLiveBroadcasts')->andThrow(
            new \Google\Service\Exception('liveStreamingNotEnabled', 403, null, [['reason' => 'liveStreamingNotEnabled']])
        );

        $youtube = Mockery::mock(YouTube::class);
        $youtube->channels = $channels;
        $youtube->liveBroadcasts = $broadcasts;

        $svc = new AuthenticateService(oauth: $oauth, youtube: $youtube);
        $r = $svc->authenticateWithCode('code');

        $this->assertFalse($r['liveStreamingEnabled']);
    }

    public function test_get_login_url_delegates_to_oauth(): void
    {
        $oauth = Mockery::mock(OAuthService::class);
        $oauth->shouldReceive('client')->andReturn(Mockery::mock(Client::class));
        $oauth->shouldReceive('getLoginUrl')->once()->with('user@example.com', 'chan-1')->andReturn('url');

        $svc = new AuthenticateService(oauth: $oauth, youtube: Mockery::mock(YouTube::class));
        $this->assertSame('url', $svc->getLoginUrl('user@example.com', 'chan-1'));
    }
}
```

- [ ] **Step 2: Run test**

Run: `vendor/bin/pest tests/Unit/Services/AuthenticateServiceTest.php`
Expected: FAIL.

- [ ] **Step 3: Implement**

`src/Services/AuthenticateService.php`:
```php
<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\Exceptions\YoutubeApiException;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\YouTube;

class AuthenticateService extends BaseService
{
    private YouTube $youtube;

    public function __construct(?OAuthService $oauth = null, ?YouTube $youtube = null)
    {
        parent::__construct($oauth);
        $this->youtube = $youtube ?? new YouTube($this->client());
    }

    public function getLoginUrl(string $youtubeEmail, ?string $channelId = null): string
    {
        return $this->oauth->getLoginUrl($youtubeEmail, $channelId);
    }

    /**
     * Exchange an auth code for a token, fetch channel details, probe live-streaming.
     *
     * @return array{token: array<string, mixed>, channel: ?array<string, mixed>, liveStreamingEnabled: bool}
     */
    public function authenticateWithCode(string $code): array
    {
        $token = $this->oauth->exchangeCode($code);
        $this->oauth->setAccessToken($token);

        return [
            'token' => $token,
            'channel' => $this->fetchChannelSnippet(),
            'liveStreamingEnabled' => $this->probeLiveStreaming(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function fetchChannelSnippet(): ?array
    {
        $resp = $this->call(fn () => $this->youtube->channels->listChannels('snippet', ['mine' => true]));
        $decoded = is_array($resp) ? $resp : (array) json_decode(json_encode($resp, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        $first = $decoded['items'][0] ?? null;
        return $first === null ? null : (array) $first;
    }

    /**
     * Read-only capability probe — replaces 1.x's create+delete-broadcast probe.
     * Costs 1 quota unit instead of ~50, with zero side effects.
     */
    private function probeLiveStreaming(): bool
    {
        try {
            $this->youtube->liveBroadcasts->listLiveBroadcasts('id', [
                'mine' => true,
                'maxResults' => 1,
            ]);
            return true;
        } catch (GoogleServiceException $e) {
            $reasons = array_column($e->getErrors() ?? [], 'reason');
            if (in_array('liveStreamingNotEnabled', $reasons, true)) {
                return false;
            }
            throw YoutubeApiException::fromGoogleException($e);
        }
    }
}
```

- [ ] **Step 4: Run tests**

Run: `vendor/bin/pest tests/Unit/Services/AuthenticateServiceTest.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add src/Services/AuthenticateService.php tests/Unit/Services/AuthenticateServiceTest.php
git commit -m "feat: AuthenticateService with read-only live-streaming probe (replaces 1.x liveStreamTest)"
```

---

## Phase 11: Remove old source files

### Task 11.1: Delete legacy 1.x classes

**Files:**
- Delete: `src/Auth/AuthService.php`
- Delete: `src/AuthenticateService.php`
- Delete: `src/ChannelService.php`
- Delete: `src/LiveStreamService.php`
- Delete: `src/VideoService.php`

- [ ] **Step 1: Delete files**

```bash
git rm src/Auth/AuthService.php src/AuthenticateService.php src/ChannelService.php src/LiveStreamService.php src/VideoService.php
```

- [ ] **Step 2: Verify autoload**

Run: `composer dump-autoload`
Expected: no errors. The `Alchemyguy\YoutubeLaravelApi\Auth\OAuthService`, `Services\…` paths resolve.

- [ ] **Step 3: Run full unit suite**

Run: `composer test:unit`
Expected: all tests PASS.

- [ ] **Step 4: Commit**

```bash
git commit -m "refactor: remove legacy 1.x service files"
```

---

## Phase 12: Static analysis & code style

### Task 12.1: Run Pint and commit style fixes

**Files:** any in `src/` or `tests/` that Pint reformats

- [ ] **Step 1: Run Pint in test mode**

Run: `composer lint`
Expected: list of files needing formatting (or "All checks passed").

- [ ] **Step 2: Apply formatting**

Run: `composer fix`
Expected: files reformatted in place.

- [ ] **Step 3: Re-run tests to confirm nothing broke**

Run: `composer test:unit`
Expected: all tests still PASS.

- [ ] **Step 4: Commit**

```bash
git add -u
git commit -m "style: apply Pint formatting"
```

### Task 12.2: PHPStan level 8

**Files:** any in `src/` or `tests/` that PHPStan reports errors for

- [ ] **Step 1: Run PHPStan**

Run: `composer analyse`
Expected: report listing any type errors.

- [ ] **Step 2: Fix each reported error**

For each reported error, **read the surrounding context** in the affected file and apply the most idiomatic fix:
- Missing nullable on a property → add `?`
- Loose array access → guard with `isset()` or `array_key_exists()`
- Missing iterable type hints → add `@param array<…>` PHPDoc
- "Cannot call method on possibly false" → guard with type check

Do not blanket `@phpstan-ignore-line` — fix the underlying issue. The only acceptable global ignore is the `missingType.iterableValue` already in `phpstan.neon`.

- [ ] **Step 3: Re-run analysis**

Run: `composer analyse`
Expected: 0 errors.

- [ ] **Step 4: Re-run tests**

Run: `composer test:unit`
Expected: all PASS.

- [ ] **Step 5: Commit**

```bash
git add -u
git commit -m "chore: PHPStan level 8 clean"
```

### Task 12.3: Rector dry-run

**Files:** any in `src/` or `tests/` that Rector wants to modify

- [ ] **Step 1: Run Rector dry-run**

Run: `composer rector`
Expected: list of suggested changes (or "No files changed").

- [ ] **Step 2: Review suggestions**

Read each suggestion in the dry-run output. Most should be no-ops because we wrote modern code from scratch. Apply only changes that don't conflict with our existing style (e.g., further type widening Rector suggests for parameters can be skipped if our signatures are already explicit).

- [ ] **Step 3: Apply selected changes**

If any worth applying:
```bash
vendor/bin/rector process
composer lint && composer fix
```

- [ ] **Step 4: Re-run tests**

Run: `composer test:unit`
Expected: all PASS.

- [ ] **Step 5: Commit (only if changes were applied)**

```bash
git add -u
git commit -m "chore: Rector cleanup pass"
```

---

## Phase 13: CI workflows

### Task 13.1: Tests workflow

**Files:**
- Create: `.github/workflows/tests.yml`

- [ ] **Step 1: Write the workflow**

```yaml
name: tests

on:
  push:
    branches: [master, "feat/**"]
  pull_request:
    branches: [master]

jobs:
  test:
    runs-on: ubuntu-latest

    strategy:
      fail-fast: true
      matrix:
        php: ["8.3", "8.4"]
        laravel: [11.*, 12.*]
        dependency-version: [prefer-lowest, prefer-stable]
        include:
          - laravel: 11.*
            testbench: 9.*
          - laravel: 12.*
            testbench: 10.*

    name: PHP ${{ matrix.php }} | Laravel ${{ matrix.laravel }} | ${{ matrix.dependency-version }}

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: dom, curl, libxml, mbstring, zip, fileinfo
          coverage: none

      - name: Install dependencies
        run: |
          composer require "laravel/framework:${{ matrix.laravel }}" "orchestra/testbench:${{ matrix.testbench }}" --no-interaction --no-update
          composer update --${{ matrix.dependency-version }} --prefer-dist --no-interaction --no-progress

      - name: Run unit tests
        run: composer test:unit
```

- [ ] **Step 2: Commit**

```bash
git add .github/workflows/tests.yml
git commit -m "ci: add tests workflow (PHP 8.3/8.4 × Laravel 11/12 matrix)"
```

### Task 13.2: Static-analysis workflow

**Files:**
- Create: `.github/workflows/static-analysis.yml`

- [ ] **Step 1: Write the workflow**

```yaml
name: static-analysis

on:
  push:
    branches: [master, "feat/**"]
  pull_request:
    branches: [master]

jobs:
  pint:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.3"
          coverage: none
      - run: composer install --no-interaction --no-progress
      - run: composer lint

  phpstan:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.3"
          coverage: none
      - run: composer install --no-interaction --no-progress
      - run: composer analyse
```

- [ ] **Step 2: Commit**

```bash
git add .github/workflows/static-analysis.yml
git commit -m "ci: add Pint + PHPStan workflow"
```

### Task 13.3: Dependabot

**Files:**
- Create: `.github/dependabot.yml`

- [ ] **Step 1: Write config**

```yaml
version: 2
updates:
  - package-ecosystem: "composer"
    directory: "/"
    schedule:
      interval: "weekly"
    open-pull-requests-limit: 5
    labels: ["dependencies", "composer"]

  - package-ecosystem: "github-actions"
    directory: "/"
    schedule:
      interval: "weekly"
    labels: ["dependencies", "github-actions"]

  - package-ecosystem: "npm"
    directory: "/docs"
    schedule:
      interval: "weekly"
    labels: ["dependencies", "docs"]
```

- [ ] **Step 2: Commit**

```bash
git add .github/dependabot.yml
git commit -m "ci: add Dependabot for composer, actions, npm"
```

### Task 13.4: Issue and PR templates

**Files:**
- Create: `.github/ISSUE_TEMPLATE/bug_report.yml`
- Create: `.github/ISSUE_TEMPLATE/feature_request.yml`
- Create: `.github/pull_request_template.md`
- Delete: `ISSUE_TEMPLATE.md` (root)
- Delete: `PULL_REQUEST_TEMPLATE.md` (root)

- [ ] **Step 1: Write `bug_report.yml`**

```yaml
name: Bug report
description: Report a bug or unexpected behavior
labels: ["bug", "needs-triage"]
body:
  - type: input
    id: package-version
    attributes:
      label: Package version
      description: e.g. 2.0.0
    validations:
      required: true
  - type: input
    id: php-version
    attributes:
      label: PHP version
    validations:
      required: true
  - type: input
    id: laravel-version
    attributes:
      label: Laravel version
    validations:
      required: true
  - type: textarea
    id: description
    attributes:
      label: Description
      description: What happened? What did you expect to happen?
    validations:
      required: true
  - type: textarea
    id: reproduction
    attributes:
      label: Reproduction
      description: Minimal code that reproduces the issue.
      render: php
    validations:
      required: true
  - type: textarea
    id: stack-trace
    attributes:
      label: Stack trace (if any)
      render: text
```

- [ ] **Step 2: Write `feature_request.yml`**

```yaml
name: Feature request
description: Propose a new feature or enhancement
labels: ["enhancement"]
body:
  - type: textarea
    id: problem
    attributes:
      label: Problem
      description: What problem are you trying to solve?
    validations:
      required: true
  - type: textarea
    id: proposal
    attributes:
      label: Proposed solution
    validations:
      required: true
  - type: textarea
    id: alternatives
    attributes:
      label: Alternatives considered
```

- [ ] **Step 3: Write `pull_request_template.md`**

```markdown
## Summary

<!-- 1-3 bullets describing what changed and why -->

## Type of change

- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Checklist

- [ ] I have read CONTRIBUTING.md
- [ ] Tests added or updated
- [ ] `composer test:unit` passes locally
- [ ] `composer lint` passes locally
- [ ] `composer analyse` passes locally
- [ ] CHANGELOG.md updated under `## [Unreleased]`
- [ ] If this is a breaking change, UPGRADE.md updated

## Related issues

<!-- e.g. Fixes #123 -->
```

- [ ] **Step 4: Delete legacy templates**

```bash
git rm ISSUE_TEMPLATE.md PULL_REQUEST_TEMPLATE.md
```

- [ ] **Step 5: Commit**

```bash
git add .github/ISSUE_TEMPLATE .github/pull_request_template.md
git commit -m "ci: GitHub issue forms and PR template"
```

## Phase 14: Documentation site (VitePress on GitHub Pages)

### Task 14.1: Initialize VitePress

**Files:**
- Create: `docs/package.json`
- Create: `docs/.vitepress/config.ts`

- [ ] **Step 1: Write `docs/package.json`**

```json
{
  "name": "youtube-laravel-api-docs",
  "version": "0.0.0",
  "private": true,
  "scripts": {
    "dev": "vitepress dev",
    "build": "vitepress build",
    "preview": "vitepress preview"
  },
  "devDependencies": {
    "vitepress": "^1.5.0",
    "vue": "^3.5.0"
  }
}
```

- [ ] **Step 2: Write `docs/.vitepress/config.ts`**

```ts
import { defineConfig } from 'vitepress'

export default defineConfig({
  title: 'YoutubeLaravelApi',
  description: 'Modern Laravel wrapper for the YouTube Data API v3',

  base: '/YoutubeLaravelApi/',

  head: [
    ['link', { rel: 'icon', href: '/YoutubeLaravelApi/favicon.svg' }],
    ['meta', { name: 'theme-color', content: '#FF0000' }],
  ],

  themeConfig: {
    logo: '/logo.svg',

    nav: [
      { text: 'Guide', link: '/guide/installation' },
      { text: 'API', link: '/api/' },
      { text: 'Examples', link: '/examples/creating-a-broadcast' },
      { text: 'Upgrading', link: '/upgrading/from-1.x' },
      {
        text: 'v2.0.0',
        items: [
          { text: 'Changelog', link: 'https://github.com/alchemyguy/YoutubeLaravelApi/blob/master/CHANGELOG.md' },
          { text: 'Releases', link: 'https://github.com/alchemyguy/YoutubeLaravelApi/releases' },
        ],
      },
    ],

    sidebar: {
      '/guide/': [
        {
          text: 'Getting started',
          items: [
            { text: 'Installation', link: '/guide/installation' },
            { text: 'Configuration', link: '/guide/configuration' },
            { text: 'Authentication', link: '/guide/authentication' },
          ],
        },
        {
          text: 'Features',
          items: [
            { text: 'Live streaming', link: '/guide/live-streaming' },
            { text: 'Channels', link: '/guide/channels' },
            { text: 'Videos', link: '/guide/videos' },
          ],
        },
        {
          text: 'Reference',
          items: [
            { text: 'Error handling', link: '/guide/error-handling' },
            { text: 'Testing', link: '/guide/testing' },
          ],
        },
      ],
      '/api/': [{ text: 'API Reference', items: [] }],
      '/examples/': [
        {
          text: 'Examples',
          items: [
            { text: 'Creating a broadcast', link: '/examples/creating-a-broadcast' },
            { text: 'Uploading a video', link: '/examples/uploading-a-video' },
            { text: 'Multi-account', link: '/examples/multi-account' },
          ],
        },
      ],
      '/upgrading/': [
        {
          text: 'Upgrading',
          items: [{ text: 'From 1.x to 2.0', link: '/upgrading/from-1.x' }],
        },
      ],
    },

    socialLinks: [
      { icon: 'github', link: 'https://github.com/alchemyguy/YoutubeLaravelApi' },
    ],

    footer: {
      message: 'Released under the MIT License.',
      copyright: 'Copyright © 2018–present Mukesh Chandra',
    },

    search: { provider: 'local' },
  },
})
```

- [ ] **Step 3: Install dependencies**

```bash
cd docs && npm install && cd ..
```
Expected: `node_modules/` populated. (gitignored.)

- [ ] **Step 4: Commit**

```bash
git add docs/package.json docs/package-lock.json docs/.vitepress/config.ts
git commit -m "docs: initialize VitePress site config"
```

### Task 14.2: Landing page

**Files:**
- Create: `docs/index.md`

- [ ] **Step 1: Write `docs/index.md`**

```markdown
---
layout: home

hero:
  name: "YoutubeLaravelApi"
  text: "YouTube Data API v3 for Laravel"
  tagline: OAuth, live streaming, channels, video uploads — typed, tested, maintained.
  actions:
    - theme: brand
      text: Get started
      link: /guide/installation
    - theme: alt
      text: View on GitHub
      link: https://github.com/alchemyguy/YoutubeLaravelApi

features:
  - icon: 🔐
    title: OAuth out of the box
    details: Drop-in OAuth flow with offline access, token refresh events, and capability detection.
  - icon: 📺
    title: Live broadcast control
    details: Create, update, transition, and delete YouTube Live broadcasts with proper RTMP stream binding.
  - icon: 🎬
    title: Video uploads
    details: Resumable, chunked uploads with safe defer-mode handling and typed metadata DTOs.
  - icon: 🛠
    title: Built for testing
    details: Inject your own Google\Client via withClient() — no global facades, no surprises.
  - icon: ⚡
    title: Modern stack
    details: PHP 8.3+, Laravel 11/12, google/apiclient ^2.18, typed exceptions, enums, readonly DTOs.
  - icon: 📖
    title: Documented
    details: Full guide, API reference, upgrade path from 1.x.
---
```

- [ ] **Step 2: Commit**

```bash
git add docs/index.md
git commit -m "docs: landing page"
```

### Task 14.3: Installation, configuration, and authentication guides

**Files:**
- Create: `docs/guide/installation.md`
- Create: `docs/guide/configuration.md`
- Create: `docs/guide/authentication.md`

- [ ] **Step 1: Write `docs/guide/installation.md`**

````markdown
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
````

- [ ] **Step 2: Write `docs/guide/configuration.md`**

````markdown
# Configuration

## Where credentials come from

The package reads credentials from `config/youtube.php`, which by default falls through to env vars (recommended). You can also hardcode for one-off scripts.

## Getting Google credentials

1. Go to [Google Cloud Console](https://console.cloud.google.com).
2. Create a project (or select an existing one).
3. Enable the **YouTube Data API v3**.
4. Create credentials:
   - **API key** for public read-only calls.
   - **OAuth 2.0 client ID** (type: Web application) for user-authorized calls.
5. Add your callback URL under **Authorized redirect URIs**.
6. Copy `client_id`, `client_secret`, `api_key`, and the callback URL into your `.env`.

## Config file reference

```php
return [
    'app_name'      => env('YOUTUBE_APP_NAME', 'YoutubeLaravelApi'),
    'client_id'     => env('YOUTUBE_CLIENT_ID'),
    'client_secret' => env('YOUTUBE_CLIENT_SECRET'),
    'api_key'       => env('YOUTUBE_API_KEY'),
    'redirect_url'  => env('YOUTUBE_REDIRECT_URL'),
    'languages'     => [/* code map for broadcast metadata */],
];
```

## Customizing the Google client

If you need to override anything about the underlying `Google\Client` (custom HTTP handler, additional scopes, ...), bind a replacement factory in your `AppServiceProvider`:

```php
use Alchemyguy\YoutubeLaravelApi\Support\YoutubeClientFactory;
use Google\Client;

$this->app->extend(YoutubeClientFactory::class, function (YoutubeClientFactory $factory) {
    return new class([]) extends YoutubeClientFactory {
        public function make(): Client {
            $client = parent::make();
            $client->setHttpClient(/* your custom Guzzle client */);
            return $client;
        }
    };
});
```
````

- [ ] **Step 3: Write `docs/guide/authentication.md`**

````markdown
# Authentication

The package uses Google's OAuth 2.0 with `access_type=offline` so you receive a refresh token along with the access token.

## Step 1: Generate the login URL

```php
use Alchemyguy\YoutubeLaravelApi\Services\AuthenticateService;

$auth = app(AuthenticateService::class);

$url = $auth->getLoginUrl(
    youtubeEmail: 'creator@example.com',
    channelId: 'your-internal-channel-identifier'
);

return redirect($url);
```

The `channelId` is round-tripped via OAuth `state` so you can correlate the callback with your application's record.

## Step 2: Handle the callback

```php
public function callback(Request $request, AuthenticateService $auth)
{
    $code = $request->query('code');
    $identifier = $request->query('state');

    $result = $auth->authenticateWithCode($code);

    // Persist somewhere durable
    Channel::find($identifier)->update([
        'youtube_token' => $result['token'],
        'youtube_channel_id' => $result['channel']['id'] ?? null,
        'live_streaming_enabled' => $result['liveStreamingEnabled'],
    ]);
}
```

`$result` is shaped:

```php
[
    'token' => ['access_token' => '…', 'refresh_token' => '…', 'expires_in' => 3600, …],
    'channel' => ['id' => 'UC…', 'snippet' => […]],   // null if no channel
    'liveStreamingEnabled' => true|false,
]
```

## Token refresh

Whenever a service applies an expired token, it automatically refreshes via the stored refresh token. The package dispatches a `TokenRefreshed` event so you can persist the new token:

```php
use Alchemyguy\YoutubeLaravelApi\Events\TokenRefreshed;
use Illuminate\Support\Facades\Event;

Event::listen(function (TokenRefreshed $event) {
    Channel::where('youtube_token->access_token', $event->oldToken['access_token'])
        ->update(['youtube_token' => $event->newToken]);
});
```

You can also persist eagerly: every service method that takes a `$token` returns implicitly via the event, but for explicit handling you can call `OAuthService::setAccessToken($token)` yourself and persist its return value.
````

- [ ] **Step 4: Commit**

```bash
git add docs/guide/installation.md docs/guide/configuration.md docs/guide/authentication.md
git commit -m "docs: installation, configuration, authentication guides"
```

### Task 14.4: Live streaming, channels, videos guides

**Files:**
- Create: `docs/guide/live-streaming.md`
- Create: `docs/guide/channels.md`
- Create: `docs/guide/videos.md`

- [ ] **Step 1: Write `docs/guide/live-streaming.md`**

````markdown
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
````

- [ ] **Step 2: Write `docs/guide/channels.md`**

````markdown
# Channels

## Public lookup (no token)

```php
use Alchemyguy\YoutubeLaravelApi\Services\ChannelService;

$channels = app(ChannelService::class);

$result = $channels->listById(['id' => 'UCxyz,UCabc']);
$result = $channels->listById(['forUsername' => 'GoogleDevelopers']);
```

## Authorized channel details

```php
$mine = $channels->getOwnChannel($token);  // returns ?array
```

Returns `null` if the token is valid but the user has no associated channel.

## Subscriptions list

```php
$subs = $channels->subscriptions([
    'channelId' => 'UCxyz',
    'totalResults' => 100,
]);
// $subs is array<int, array{kind: string, channelId: string}>
```

The package paginates internally up to `totalResults` (50 per request).

## Subscribe / unsubscribe

::: warning
YouTube heavily rate-limits subscription writes (anti-bot). Expect frequent rejections for non-interactive automation.
:::

```php
$channels->subscribe($token, 'UC-target-channel');

// To unsubscribe, you need the subscription ID from the subscriptions list
$channels->unsubscribe($token, 'subscription-id');
```

## Update branding

```php
use Alchemyguy\YoutubeLaravelApi\DTOs\BrandingProperties;

$channels->updateBranding($token, new BrandingProperties(
    channelId: 'UCxyz',
    description: 'New description',
    keywords: 'laravel, php, php',
    defaultLanguage: 'en',
));
```
````

- [ ] **Step 3: Write `docs/guide/videos.md`**

````markdown
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
````

- [ ] **Step 4: Commit**

```bash
git add docs/guide/live-streaming.md docs/guide/channels.md docs/guide/videos.md
git commit -m "docs: live streaming, channels, videos guides"
```

### Task 14.5: Error-handling and testing guides

**Files:**
- Create: `docs/guide/error-handling.md`
- Create: `docs/guide/testing.md`

- [ ] **Step 1: Write `docs/guide/error-handling.md`**

````markdown
# Error handling

The package raises typed exceptions extending `YoutubeException`:

```
YoutubeException (extends \RuntimeException)
├── ConfigurationException        // missing/invalid config
├── AuthenticationException       // OAuth code or refresh failure
├── LiveStreamingNotEnabledException
└── YoutubeApiException           // wraps Google\Service\Exception
    └── QuotaExceededException
```

## Catching specific errors

```php
use Alchemyguy\YoutubeLaravelApi\Exceptions\QuotaExceededException;
use Alchemyguy\YoutubeLaravelApi\Exceptions\AuthenticationException;
use Alchemyguy\YoutubeLaravelApi\Exceptions\YoutubeApiException;

try {
    $videos->upload($token, $path, $data);
} catch (QuotaExceededException $e) {
    // back off until tomorrow's quota reset
} catch (AuthenticationException $e) {
    // re-prompt user to reauthorize
} catch (YoutubeApiException $e) {
    // any other API error — inspect $e->getGoogleErrors()
}
```

## Backward compatibility

All package exceptions extend `\RuntimeException`, so 1.x callers using `catch (\Exception $e)` continue to work — they just gain more granular options.

## DTO validation errors

DTOs throw stdlib `\InvalidArgumentException` for invalid input (past start times, bad date formats, oversized tags). These are *outside* the `YoutubeException` hierarchy because invalid input is a programming error, not a runtime API failure.
````

- [ ] **Step 2: Write `docs/guide/testing.md`**

````markdown
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
````

- [ ] **Step 3: Commit**

```bash
git add docs/guide/error-handling.md docs/guide/testing.md
git commit -m "docs: error-handling and testing guides"
```

### Task 14.6: Examples

**Files:**
- Create: `docs/examples/creating-a-broadcast.md`
- Create: `docs/examples/uploading-a-video.md`
- Create: `docs/examples/multi-account.md`

- [ ] **Step 1: Write `docs/examples/creating-a-broadcast.md`**

````markdown
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
````

- [ ] **Step 2: Write `docs/examples/uploading-a-video.md`**

````markdown
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
````

- [ ] **Step 3: Write `docs/examples/multi-account.md`**

````markdown
# Example: Managing multiple YouTube accounts

The default DI flow assumes a single Google client built from `config/youtube.php`. For SaaS scenarios — one set of OAuth client credentials, many user channels — you can build a per-request client.

```php
use Alchemyguy\YoutubeLaravelApi\Services\LiveStream\LiveStreamService;
use Alchemyguy\YoutubeLaravelApi\Support\YoutubeClientFactory;
use Google\Client;

$factory = app(YoutubeClientFactory::class);

foreach (Channel::cursor() as $channel) {
    $client = $factory->make();   // fresh client each iteration
    $live = LiveStreamService::withClient($client);

    foreach ($channel->scheduledBroadcasts as $sched) {
        $live->broadcast($channel->youtube_token, $sched->toBroadcastData());
    }
}
```

Each `withClient()` returns an isolated service instance — no token bleed-through.
````

- [ ] **Step 4: Commit**

```bash
git add docs/examples
git commit -m "docs: examples (broadcast, video upload, multi-account)"
```

### Task 14.7: Upgrade guide page (mirrors UPGRADE.md)

**Files:**
- Create: `docs/upgrading/from-1.x.md`

- [ ] **Step 1: Write the page**

This page mirrors `UPGRADE.md` (created in Phase 15). For now, write a placeholder that auto-includes UPGRADE.md once it exists, or duplicate the same content. Since UPGRADE.md is created in Phase 15, write the file with this content:

```markdown
# Upgrading from 1.x to 2.0

::: tip
This page mirrors [UPGRADE.md](https://github.com/alchemyguy/YoutubeLaravelApi/blob/master/UPGRADE.md) in the repository — keep them in sync.
:::

<!-- The following content is duplicated from UPGRADE.md. -->

[Content will be inserted from UPGRADE.md once Phase 15 lands.]
```

After Phase 15 lands, return here and copy the body of `UPGRADE.md` into this page.

- [ ] **Step 2: Commit**

```bash
git add docs/upgrading/from-1.x.md
git commit -m "docs: upgrade guide placeholder"
```

### Task 14.8: Docs deploy workflow + API ref auto-generation

**Files:**
- Create: `.github/workflows/docs.yml`

- [ ] **Step 1: Write the workflow**

```yaml
name: docs

on:
  push:
    branches: [master]
  workflow_dispatch:

permissions:
  contents: read
  pages: write
  id-token: write

concurrency:
  group: pages
  cancel-in-progress: false

jobs:
  build:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4

      - uses: shivammathur/setup-php@v2
        with:
          php-version: "8.3"

      - uses: actions/setup-node@v4
        with:
          node-version: 20
          cache: npm
          cache-dependency-path: docs/package-lock.json

      - name: Install Composer deps
        run: composer install --no-interaction --no-progress

      - name: Generate API reference
        run: |
          # phpDocumentor + a Node script that converts XML output to Markdown
          # under docs/api/. Implementation deferred until first docs deploy
          # — see Task 14.8 step 3 if blocking.
          mkdir -p docs/api
          echo "# API Reference" > docs/api/index.md
          echo "_Auto-generated reference will land here in a follow-up._" >> docs/api/index.md

      - name: Install npm deps
        run: cd docs && npm ci

      - name: Build site
        run: cd docs && npm run build

      - uses: actions/upload-pages-artifact@v3
        with:
          path: docs/.vitepress/dist

  deploy:
    needs: build
    runs-on: ubuntu-latest
    environment:
      name: github-pages
      url: ${{ steps.deployment.outputs.page_url }}
    steps:
      - id: deployment
        uses: actions/deploy-pages@v4
```

- [ ] **Step 2: Commit**

```bash
git add .github/workflows/docs.yml
git commit -m "ci: docs deploy to GitHub Pages"
```

- [ ] **Step 3: GitHub repo settings (manual, post-merge)**

After merging the PR, in the GitHub repo:
1. Settings → Pages → **Source: GitHub Actions**.
2. Verify branch protection on `master` requires the `tests` and `static-analysis` checks.

Document this in the PR description.

## Phase 15: Release prep

### Task 15.1: Write `UPGRADE.md`

**Files:**
- Create: `UPGRADE.md`

- [ ] **Step 1: Write the file**

```markdown
# Upgrading from 1.x to 2.0

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
```

- [ ] **Step 2: Commit**

```bash
git add UPGRADE.md
git commit -m "docs: UPGRADE.md for 1.x to 2.0 migration"
```

### Task 15.2: Backfill the docs upgrade page

**Files:**
- Modify: `docs/upgrading/from-1.x.md`

- [ ] **Step 1: Replace placeholder with full content**

Copy the body of `UPGRADE.md` (everything below the H1) into `docs/upgrading/from-1.x.md`, keeping the H1 as `# Upgrading from 1.x to 2.0`. The two files should now be content-identical.

- [ ] **Step 2: Commit**

```bash
git add docs/upgrading/from-1.x.md
git commit -m "docs: backfill upgrade guide from UPGRADE.md"
```

### Task 15.3: Write `CHANGELOG.md`

**Files:**
- Create: `CHANGELOG.md`

- [ ] **Step 1: Write the file**

```markdown
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
```

- [ ] **Step 2: Commit**

```bash
git add CHANGELOG.md
git commit -m "docs: CHANGELOG.md following Keep a Changelog"
```

### Task 15.4: Rewrite `README.md`

**Files:**
- Modify: `README.md`

- [ ] **Step 1: Replace contents**

The new README is concise (the docs site is the canonical reference):

```markdown
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
```

- [ ] **Step 2: Commit**

```bash
git add README.md
git commit -m "docs: rewrite README for 2.0"
```

### Task 15.5: Final verification

**Files:** none

- [ ] **Step 1: Run full check**

```bash
composer install
composer test:unit
composer lint
composer analyse
```

All four commands must succeed with zero errors. If any fail, do NOT proceed — fix them and recommit before continuing.

- [ ] **Step 2: Build docs locally**

```bash
cd docs
npm install
npm run build
cd ..
```

Expected: `docs/.vitepress/dist/` populated with no errors.

- [ ] **Step 3: Spot-check the rendered docs**

```bash
cd docs
npm run preview
```

Open `http://localhost:4173/YoutubeLaravelApi/` (the path matches the `base` config). Click through:
- Landing page renders.
- Sidebar navigation works for all four sections (guide / api / examples / upgrading).
- Code blocks render with PHP highlighting.
- No 404s on internal links.

Stop the server (`Ctrl-C`).

- [ ] **Step 4: Spec coverage check**

Open `docs/superpowers/specs/2026-05-02-youtube-laravel-api-2.0-modernization-design.md` alongside this plan and walk through each section:
- §1 Goals: every goal has tasks.
- §2 Version matrix: PHP 8.3, Laravel 11/12 in `composer.json` (Task 0.1) and `tests.yml` matrix (Task 13.1) ✓
- §3 Architecture: factory + provider + services + managers all built ✓
- §4 Modules: every file in §4.1 has a creation task ✓
- §5 Removed/replaced: `relatedToVideoId` not in `VideoService`; `liveStreamTest` replaced by read-only probe in `AuthenticateService` ✓
- §6 Bug fixes: every numbered bug has either a regression test or a structural fix ✓
- §7 Exceptions: hierarchy in Phase 1 ✓
- §8 Testing: layout matches, framework matches ✓
- §9 Tooling: Pint, PHPStan, Rector all configured ✓
- §10 CI: tests.yml + static-analysis.yml + dependabot ✓
- §11 Docs site: VitePress + all guide pages ✓
- §12 Composer scripts: all in `composer.json` ✓
- §13 Branch & release: covered in workflow ✓
- §14 Files added/removed: tracked across phases ✓
- §15 UPGRADE.md outline: matches Phase 15 ✓

If any item is uncovered, add a follow-up task before proceeding.

### Task 15.6: Push branch and open PR

**Files:** none

- [ ] **Step 1: Push the branch**

```bash
git push -u origin feat/2.0-modernization
```

- [ ] **Step 2: Open the PR**

```bash
gh pr create --base master --title "feat: 2.0 modernization (PHP 8.3+, Laravel 11+, full bug-fix sweep)" --body "$(cat <<'EOF'
## Summary

Major rewrite landing as v2.0.0:
- **Versions**: PHP 8.3+, Laravel 11/12, google/apiclient ^2.18
- **Bug fixes**: 17 catalogued bugs from 1.x, all with regression tests
- **DI refactor**: `\Config::` facade replaced with `YoutubeClientFactory` + container bindings; every service has `withClient()` for tests
- **API shape**: typed exceptions, enums (`BroadcastStatus`, `Rating`, `PrivacyStatus`), readonly DTOs (`BroadcastData`, `VideoUploadData`, `BrandingProperties`)
- **`LiveStreamService` split**: orchestrator + `BroadcastManager`, `StreamManager`, `ThumbnailUploader`
- **Removed**: `relatedToVideoId` (Google killed the parameter Aug 2023), `AuthenticateService::deleteEvent` (accidentally-exposed helper)
- **Replaced**: `liveStreamTest` (was create+delete probe, now read-only — saves ~50 quota units per auth)

## Key files

- Spec: `docs/superpowers/specs/2026-05-02-youtube-laravel-api-2.0-modernization-design.md`
- Plan: `docs/superpowers/plans/2026-05-02-youtube-laravel-api-2.0-modernization.md`
- Migration guide: `UPGRADE.md`
- Changelog: `CHANGELOG.md`

## Breaking changes

This is a major version bump. Anyone on 1.x must follow `UPGRADE.md` to migrate. Highlights:
- Namespace `alchemyguy\` → `Alchemyguy\`
- Config file `google-config.php` → `youtube.php`; env vars uppercase with `YOUTUBE_` prefix
- Method renames across all services (full table in UPGRADE.md)
- `broadcast()` throws on past start times instead of silently using "now"
- Typed exceptions (still extend `\RuntimeException`, so bare `\Exception` catches still work)

## Test plan

- [ ] CI green on all matrix legs (PHP 8.3/8.4 × Laravel 11/12 × prefer-lowest/stable)
- [ ] PHPStan level 8 clean
- [ ] Pint clean
- [ ] Local integration tests pass against sandbox YouTube channel (set `YOUTUBE_INTEGRATION_TEST_TOKEN` env var)
- [ ] Docs site builds and renders correctly via `cd docs && npm run preview`
- [ ] Manual smoke test: install in a Laravel 12 app, run OAuth flow, create+transition+delete a broadcast

## Post-merge tasks

- [ ] Repo Settings → Pages → Source: GitHub Actions
- [ ] Branch protection on `master`: require `tests` and `static-analysis` checks, 1 approval, no force-push, linear history
- [ ] Tag `v2.0.0-beta.1` and announce to gather feedback
- [ ] After ~2 weeks of beta with no critical issues, tag `v2.0.0`
- [ ] Verify Packagist auto-update fires

🤖 Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

- [ ] **Step 3: Verify PR opened**

The command output prints the PR URL. Open it in a browser; confirm:
- Title and body rendered correctly.
- All commits from `feat/2.0-modernization` are listed.
- CI checks are running (tests, static-analysis, docs).

- [ ] **Step 4: Wait for CI**

Run: `gh pr checks` (or watch in browser).
Expected: all checks pass. If any fail, fix and push another commit; do not merge with red checks.

---

## Plan complete

When all phases are checked off and the PR is open with green CI, this implementation is done. Hand the PR link back to the user.

## Self-review notes (for the implementer)

- **DRY**: Each service uses `BaseService::call()` to wrap Google exceptions; do not re-implement try/catch in subclasses.
- **YAGNI**: Resist adding helper methods not required by the plan. The package is intentionally small.
- **TDD**: Every implementation step is preceded by a failing test step. Do not skip the failing-test verification — if the test passes when it should fail, your test is wrong.
- **Frequent commits**: One commit per task minimum. If a task's "implement" step touches three unrelated files, split into multiple tasks.
- **Type-consistency check**: If you renamed a method (e.g. `delete` vs `deleteEvent`), grep the codebase to confirm no stale call sites remain. The plan tries to maintain consistency, but if you spot a mismatch between an earlier and later task, the later task wins — fix the earlier one and re-run its tests.
