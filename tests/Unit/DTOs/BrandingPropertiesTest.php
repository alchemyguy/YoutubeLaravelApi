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
