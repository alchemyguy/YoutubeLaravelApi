<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\DTOs;

final readonly class BrandingProperties
{
    public function __construct(
        public string $channelId,
        public ?string $description = null,
        public ?string $keywords = null,
        public ?string $defaultLanguage = null,
        public ?string $defaultTab = null,
        public ?bool $moderateComments = null,
        public ?bool $showRelatedChannels = null,
        public ?bool $showBrowseView = null,
        public ?string $featuredChannelsTitle = null,
        public ?string $featuredChannelsUrls = null,
        public ?string $unsubscribedTrailer = null,
    ) {}

    /** @return array<string, mixed> */
    public function toDottedArray(): array
    {
        $map = [
            'description' => 'brandingSettings.channel.description',
            'keywords' => 'brandingSettings.channel.keywords',
            'defaultLanguage' => 'brandingSettings.channel.defaultLanguage',
            'defaultTab' => 'brandingSettings.channel.defaultTab',
            'moderateComments' => 'brandingSettings.channel.moderateComments',
            'showRelatedChannels' => 'brandingSettings.channel.showRelatedChannels',
            'showBrowseView' => 'brandingSettings.channel.showBrowseView',
            'featuredChannelsTitle' => 'brandingSettings.channel.featuredChannelsTitle',
            'featuredChannelsUrls' => 'brandingSettings.channel.featuredChannelsUrls[]',
            'unsubscribedTrailer' => 'brandingSettings.channel.unsubscribedTrailer',
        ];
        $out = ['id' => $this->channelId];
        foreach ($map as $prop => $dotted) {
            if ($this->{$prop} !== null) {
                $out[$dotted] = $this->{$prop};
            }
        }
        return $out;
    }
}
