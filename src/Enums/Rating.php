<?php

declare(strict_types=1);

namespace Alchemyguy\YoutubeLaravelApi\Enums;

enum Rating: string
{
    case Like = 'like';
    case Dislike = 'dislike';
    case None = 'none';
}
