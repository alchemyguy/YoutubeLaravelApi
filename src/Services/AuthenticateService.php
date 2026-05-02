<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Services;

use Alchemyguy\YoutubeLaravelApi\Auth\OAuthService;
use Alchemyguy\YoutubeLaravelApi\Exceptions\YoutubeApiException;
use Google\Service\Exception as GoogleServiceException;
use Google\Service\YouTube;

class AuthenticateService extends BaseService
{
    private YouTube $youtube;

    public function __construct(?OAuthService $oauth = null, ?YouTube $youtube = null)
    {
        parent::__construct($oauth);
        $this->youtube = $youtube ?? new YouTube($this->client());
    }

    public function getLoginUrl(string $youtubeEmail, ?string $channelId = null): string
    {
        return $this->oauth->getLoginUrl($youtubeEmail, $channelId);
    }

    /**
     * Exchange an auth code for a token, fetch channel details, probe live-streaming.
     *
     * @return array{token: array<string, mixed>, channel: ?array<string, mixed>, liveStreamingEnabled: bool}
     */
    public function authenticateWithCode(string $code): array
    {
        $token = $this->oauth->exchangeCode($code);
        $this->oauth->setAccessToken($token);

        return [
            'token' => $token,
            'channel' => $this->fetchChannelSnippet(),
            'liveStreamingEnabled' => $this->probeLiveStreaming(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function fetchChannelSnippet(): ?array
    {
        $resp = $this->call(fn () => $this->youtube->channels->listChannels('snippet', ['mine' => true]));
        $decoded = is_array($resp) ? $resp : (array) json_decode(json_encode($resp, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
        $first = $decoded['items'][0] ?? null;

        return $first === null ? null : (array) $first;
    }

    /**
     * Read-only capability probe — replaces 1.x's create+delete-broadcast probe.
     * Costs 1 quota unit instead of ~50, with zero side effects.
     */
    private function probeLiveStreaming(): bool
    {
        try {
            $this->youtube->liveBroadcasts->listLiveBroadcasts('id', [
                'mine' => true,
                'maxResults' => 1,
            ]);

            return true;
        } catch (GoogleServiceException $e) {
            $reasons = array_column($e->getErrors() ?? [], 'reason');
            if (in_array('liveStreamingNotEnabled', $reasons, true)) {
                return false;
            }
            throw YoutubeApiException::fromGoogleException($e);
        }
    }
}
