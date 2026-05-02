<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Enums;

enum PrivacyStatus: string
{
    case Public = 'public';
    case Private = 'private';
    case Unlisted = 'unlisted';
}
