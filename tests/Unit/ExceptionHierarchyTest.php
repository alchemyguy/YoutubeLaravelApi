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
