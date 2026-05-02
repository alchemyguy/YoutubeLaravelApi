<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\DTOs;

use Alchemyguy\YoutubeLaravelApi\Enums\PrivacyStatus;
use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

final readonly class BroadcastData
{
    /**
     * @param array<int, string> $tags
     */
    public function __construct(
        public string $title,
        public string $description,
        public DateTimeImmutable $scheduledStartTime,
        public ?DateTimeImmutable $scheduledEndTime = null,
        public PrivacyStatus $privacyStatus = PrivacyStatus::Public,
        public string $languageName = 'English',
        public ?string $thumbnailPath = null,
        public array $tags = [],
    ) {
        $now = new DateTimeImmutable('now', $scheduledStartTime->getTimezone());
        if ($scheduledStartTime < $now) {
            throw new InvalidArgumentException(
                "scheduledStartTime ({$scheduledStartTime->format(DATE_ATOM)}) is in the past."
            );
        }
        if ($scheduledEndTime !== null && $scheduledEndTime <= $scheduledStartTime) {
            throw new InvalidArgumentException(
                'scheduled end time must be after scheduled start time.'
            );
        }
        $totalTagLen = array_sum(array_map('strlen', $tags));
        if ($totalTagLen > 500) {
            throw new InvalidArgumentException(
                "Total tag length ({$totalTagLen}) exceeds YouTube's 500-character limit."
            );
        }
        if ($thumbnailPath !== null && ! is_file($thumbnailPath)) {
            throw new InvalidArgumentException("Thumbnail not found: {$thumbnailPath}");
        }
    }

    /**
     * Backward-compatible constructor for 1.x-style array data.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        foreach (['title', 'description', 'event_start_date_time', 'time_zone'] as $required) {
            if (! isset($data[$required]) || $data[$required] === '') {
                throw new InvalidArgumentException("Missing required field: {$required}");
            }
        }

        $tz = new DateTimeZone((string) $data['time_zone']);
        $start = DateTimeImmutable::createFromFormat(
            'Y-m-d H:i:s',
            (string) $data['event_start_date_time'],
            $tz
        );
        if ($start === false) {
            throw new InvalidArgumentException(
                "Invalid event_start_date_time '{$data['event_start_date_time']}' — expected Y-m-d H:i:s."
            );
        }

        $end = null;
        if (! empty($data['event_end_date_time'])) {
            $end = DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                (string) $data['event_end_date_time'],
                $tz
            );
            if ($end === false) {
                throw new InvalidArgumentException(
                    "Invalid event_end_date_time '{$data['event_end_date_time']}' — expected Y-m-d H:i:s."
                );
            }
        }

        return new self(
            title: (string) $data['title'],
            description: (string) $data['description'],
            scheduledStartTime: $start,
            scheduledEndTime: $end,
            privacyStatus: PrivacyStatus::from((string) ($data['privacy_status'] ?? 'public')),
            languageName: (string) ($data['language_name'] ?? 'English'),
            thumbnailPath: isset($data['thumbnail_path']) ? (string) $data['thumbnail_path'] : null,
            tags: array_values(array_filter((array) ($data['tag_array'] ?? []), 'strlen')),
        );
    }
}
