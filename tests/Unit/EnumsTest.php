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
