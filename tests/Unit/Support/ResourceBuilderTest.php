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

    public function test_skips_only_null_and_empty_string_values(): void
    {
        $r = ResourceBuilder::fromProperties([
            'a' => 'x',
            'b' => '',
            'c' => null,
        ]);
        $this->assertSame(['a' => 'x'], $r);
    }

    public function test_preserves_false_and_zero_values(): void
    {
        $r = ResourceBuilder::fromProperties([
            'flag' => false,
            'count' => 0,
            'on' => true,
        ]);
        $this->assertSame(['flag' => false, 'count' => 0, 'on' => true], $r);
    }

    public function test_array_marker_with_empty_value_returns_empty_array(): void
    {
        $r = ResourceBuilder::fromProperties(['tags[]' => null]);
        $this->assertSame([], $r);
    }
}
