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
