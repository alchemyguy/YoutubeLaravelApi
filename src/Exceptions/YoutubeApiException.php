<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Exceptions;

use Google\Service\Exception as GoogleServiceException;
use Throwable;

class YoutubeApiException extends YoutubeException
{
    /** @var array<int, array<string, mixed>> */
    protected array $googleErrors = [];

    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        array $googleErrors = [],
    ) {
        parent::__construct($message, $code, $previous);
        $this->googleErrors = $googleErrors;
    }

    public static function fromGoogleException(GoogleServiceException $e): self
    {
        $errors = $e->getErrors() ?? [];
        $reasons = array_column($errors, 'reason');

        if (in_array('quotaExceeded', $reasons, true) || in_array('rateLimitExceeded', $reasons, true)) {
            return new QuotaExceededException($e->getMessage(), $e->getCode(), $e, $errors);
        }

        if (in_array('liveStreamingNotEnabled', $reasons, true) || in_array('liveBroadcastNotFound', $reasons, true)) {
            return new self($e->getMessage(), $e->getCode(), $e, $errors);
        }

        return new self($e->getMessage(), $e->getCode(), $e, $errors);
    }

    /** @return array<int, array<string, mixed>> */
    public function getGoogleErrors(): array
    {
        return $this->googleErrors;
    }
}
