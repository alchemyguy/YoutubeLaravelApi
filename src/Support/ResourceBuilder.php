<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Support;

final class ResourceBuilder
{
    /**
     * Convert dot-notation properties (e.g. "snippet.tags[]" => "a,b,c")
     * into a nested array for Google API resources.
     *
     * @param array<string, mixed> $properties
     * @return array<string, mixed>
     */
    public static function fromProperties(array $properties): array
    {
        $resource = [];

        foreach ($properties as $path => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            self::set($resource, $path, $value);
        }

        return $resource;
    }

    /**
     * @param array<string, mixed> $resource
     */
    private static function set(array &$resource, string $path, mixed $value): void
    {
        $keys = explode('.', $path);
        $ref = &$resource;
        $isArray = false;

        foreach ($keys as $key) {
            if (str_ends_with($key, '[]')) {
                $key = substr($key, 0, -2);
                $isArray = true;
            }
            $ref = &$ref[$key];
        }

        if ($isArray) {
            $ref = is_string($value) ? explode(',', $value) : (array) $value;
        } else {
            $ref = $value;
        }
    }
}
