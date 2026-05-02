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
