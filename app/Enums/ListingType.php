<?php

declare(strict_types=1);

namespace App\Enums;

enum ListingType: string
{
    case Property = 'property';
    case Car      = 'car';
}
