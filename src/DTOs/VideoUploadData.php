<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\DTOs;

use Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus;
use InvalidArgumentException;

final readonly class VideoUploadData
{
    /**
     * @param array<int, string> $tags
     */
    public function __construct(
        public string $title,
        public string $description,
        public string $categoryId,
        public PrivacyStatus $privacyStatus,
        public array $tags = [],
        public int $chunkSizeBytes = 1048576,
    ) {
        if ($chunkSizeBytes < 262144) {
            throw new InvalidArgumentException('chunkSizeBytes must be >= 256 KiB (262144).');
        }
        if ($title === '') {
            throw new InvalidArgumentException('title is required.');
        }
    }

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        foreach (['title', 'description', 'category_id', 'video_status'] as $key) {
            if (!isset($data[$key])) {
                throw new InvalidArgumentException("Missing required field: {$key}");
            }
        }
        return new self(
            title: (string) $data['title'],
            description: (string) $data['description'],
            categoryId: (string) $data['category_id'],
            privacyStatus: PrivacyStatus::from((string) $data['video_status']),
            tags: array_values((array) ($data['tags'] ?? [])),
            chunkSizeBytes: (int) ($data['chunk_size'] ?? 1048576),
        );
    }
}
