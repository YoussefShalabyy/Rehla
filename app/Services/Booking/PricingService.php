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
        $end = Carbon::parse($checkOut);
        
        $nights = (int) $start->diffInDays($end);
        
        // Ensure at least 1 night
        if ($nights < 1) {
            $nights = 1;
        }

        $baseTotalCents = $nights * $listing->base_price_cents;
        $cleaningFeeCents = $listing->cleaning_fee_cents ?? 0;
        
        $extraGuestFeeCents = 0;
        if ($guests > $listing->max_guests) {
            $extraGuests = $guests - $listing->max_guests;
            // Extra guest fee is per night per extra guest
            $extraGuestFeeCents = $extraGuests * $nights * ($listing->extra_guest_fee_cents ?? 0);
        }

        $subtotal = $baseTotalCents + $cleaningFeeCents + $extraGuestFeeCents;

        // Platform fee percentage (default 10%)
        // The platform setting value might be a string "10" or "10.5".
        $platformFeePercentage = (float) PlatformSetting::get('platform_fee_percentage', 10);
        $platformFeeCents = (int) round($subtotal * ($platformFeePercentage / 100));

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
