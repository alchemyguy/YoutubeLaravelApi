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
