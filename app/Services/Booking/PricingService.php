<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\DTOs\Booking\PricingResultDTO;
use App\Models\Listing;
use App\Models\PlatformSetting;
use Carbon\Carbon;

class PricingService
{
    /**
     * Calculate pricing for a booking.
     * All calculations are performed in integer cents to avoid floating point errors.
     */
    public function calculate(Listing $listing, string $checkIn, string $checkOut, int $guests): PricingResultDTO
    {
        $start = Carbon::parse($checkIn);
        $end   = Carbon::parse($checkOut);

        $nights = (int) $start->diffInDays($end);

        // Ensure at least 1 night
        if ($nights < 1) {
            $nights = 1;
        }

        $baseTotalCents   = $nights * $listing->base_price_cents;
        $cleaningFeeCents = $listing->cleaning_fee_cents ?? 0;

        $extraGuestFeeCents = 0;
        if ($guests > $listing->max_guests) {
            $extraGuests        = $guests - $listing->max_guests;
            $extraGuestFeeCents = $extraGuests * $nights * ($listing->extra_guest_fee_cents ?? 0);
        }

        $subtotal = $baseTotalCents + $cleaningFeeCents + $extraGuestFeeCents;

        // Platform fee from settings (percentage of subtotal)
        $feePercentage    = (float) PlatformSetting::get('platform_fee_percentage', 0);
        $platformFeeCents = (int) round($subtotal * $feePercentage / 100);

        $grandTotalCents = $subtotal + $platformFeeCents;

        return new PricingResultDTO(
            nights: $nights,
            baseTotalCents: $baseTotalCents,
            cleaningFeeCents: $cleaningFeeCents,
            extraGuestFeeCents: $extraGuestFeeCents,
            platformFeeCents: $platformFeeCents,
            grandTotalCents: $grandTotalCents,
        );
    }
}
