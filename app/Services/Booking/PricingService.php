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

        // Platform fee waived for promotion
        $platformFeeCents = 0;

        // Taxes (5%)
        $taxesCents = (int) round($subtotal * 0.05);

        // Rehla VIP Discount (10% off base)
        $discountAmountCents = (int) round($subtotal * 0.10);

        $grandTotalCents = $subtotal + $taxesCents + $platformFeeCents - $discountAmountCents;

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
