<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Exceptions;

use Google\Service\Exception as GoogleServiceException;
use Throwable;

class YoutubeApiException extends YoutubeException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        /** @var array<int, array<string, mixed>> */
        protected array $googleErrors = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function fromGoogleException(GoogleServiceException $e): YoutubeException
    {
        $errors = $e->getErrors() ?? [];
        $reasons = array_column($errors, 'reason');

        if (in_array('quotaExceeded', $reasons, true) || in_array('rateLimitExceeded', $reasons, true)) {
            return new QuotaExceededException($e->getMessage(), $e->getCode(), $e, $errors);
        }

        if (in_array('liveStreamingNotEnabled', $reasons, true)) {
            return new LiveStreamingNotEnabledException($e->getMessage(), $e->getCode(), $e);
        }

        return new self($e->getMessage(), $e->getCode(), $e, $errors);
    }

    /** @return array<int, array<string, mixed>> */
    public function getGoogleErrors(): array
    {
        return $this->googleErrors;
    }
}
