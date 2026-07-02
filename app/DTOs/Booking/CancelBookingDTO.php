<?php

declare(strict_types=1);

namespace App\DTOs\Booking;

readonly class CancelBookingDTO
{
    public function __construct(
        public string $reason,
    ) {
    }
}
