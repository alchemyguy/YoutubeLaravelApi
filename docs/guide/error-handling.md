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
