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
