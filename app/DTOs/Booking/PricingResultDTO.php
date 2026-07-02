<?php

declare(strict_types=1);

namespace App\DTOs\Booking;

readonly class PricingResultDTO
{
    public function __construct(
        public int $nights,
        public int $baseTotalCents,
        public int $cleaningFeeCents,
        public int $extraGuestFeeCents,
        public int $platformFeeCents,
        public int $grandTotalCents,
    ) {
    }
}
