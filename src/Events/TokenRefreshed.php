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
