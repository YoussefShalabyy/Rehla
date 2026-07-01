<?php

declare(strict_types=1);

namespace App\Enums;

enum ListingStatus: string
{
    case Pending   = 'pending';
    case Published = 'published';
    case Rejected  = 'rejected';
    case Archived  = 'archived';
}
