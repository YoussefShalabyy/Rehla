<?php

declare(strict_types=1);

namespace App\Enums;

enum PropertyType: string
{
    case Hotel     = 'hotel';
    case Apartment = 'apartment';
    case Villa     = 'villa';
    case Room      = 'room';
}
