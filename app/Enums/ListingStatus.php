<?php

declare(strict_types=1);

namespace App\Enums;

enum ListingStatus: string
{
    case Active   = 'active';
    case Hidden   = 'hidden';
    case Disabled = 'disabled';
    case Archived = 'archived';
}
