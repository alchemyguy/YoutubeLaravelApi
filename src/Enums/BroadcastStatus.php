<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Enums;

enum BroadcastStatus: string
{
    case Testing = 'testing';
    case Live = 'live';
    case Complete = 'complete';
}
