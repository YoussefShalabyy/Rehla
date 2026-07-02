<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class AvailabilityService
{
    /**
     * Check if a listing is available for the given dates.
     * Must be called inside a DB transaction with lockForUpdate() on the listing to be completely race-condition safe.
     */
    public function isAvailable(Listing $listing, string $checkIn, string $checkOut): bool
    {
        $start = Carbon::parse($checkIn)->startOfDay();
        $end = Carbon::parse($checkOut)->startOfDay();

        // 1. Check existing confirmed/active/pending bookings
        $hasBookingConflict = Booking::where('listing_id', $listing->id)
            ->whereIn('status', ['pending', 'confirmed', 'active'])
            ->where(function ($query) use ($start, $end) {
                // Booking overlaps if (Booking.checkIn < Requested.checkOut) AND (Booking.checkOut > Requested.checkIn)
                $query->where('check_in_date', '<', $end->format('Y-m-d'))
                      ->where('check_out_date', '>', $start->format('Y-m-d'));
            })
            ->exists();

        if ($hasBookingConflict) {
            return false;
        }

        // 2. Check manual availability blocks
        $hasBlockConflict = AvailabilityBlock::where('listing_id', $listing->id)
            ->where(function ($query) use ($start, $end) {
                $query->where('start_date', '<', $end->format('Y-m-d'))
                      ->where('end_date', '>', $start->format('Y-m-d'));
            })
            ->exists();

        if ($hasBlockConflict) {
            return false;
        }

        return true;
    }

    /**
     * Get all blocked dates for a listing in a specific month (for frontend calendar rendering).
     */
    public function getBlockedDates(Listing $listing, string $monthYm): array
    {
        $startOfMonth = Carbon::parse($monthYm . '-01')->startOfMonth();
        $endOfMonth = $startOfMonth->copy()->endOfMonth();

        // Get Bookings
        $bookings = Booking::where('listing_id', $listing->id)
            ->whereIn('status', ['pending', 'confirmed', 'active'])
            ->where('check_in_date', '<=', $endOfMonth->format('Y-m-d'))
            ->where('check_out_date', '>=', $startOfMonth->format('Y-m-d'))
            ->get(['check_in_date', 'check_out_date']);

        // Get Manual Blocks
        $blocks = AvailabilityBlock::where('listing_id', $listing->id)
            ->where('start_date', '<=', $endOfMonth->format('Y-m-d'))
            ->where('end_date', '>=', $startOfMonth->format('Y-m-d'))
            ->get(['start_date', 'end_date']);

        $blockedDates = [];

        foreach ($bookings as $booking) {
            $current = Carbon::parse($booking->check_in_date);
            $end = Carbon::parse($booking->check_out_date);
            while ($current->lt($end)) {
                $blockedDates[] = $current->format('Y-m-d');
                $current->addDay();
            }
        }

        foreach ($blocks as $block) {
            $current = Carbon::parse($block->start_date);
            $end = Carbon::parse($block->end_date);
            // Assuming blocks are inclusive of the end date, unlike checkout where you leave in the morning
            while ($current->lte($end)) {
                $blockedDates[] = $current->format('Y-m-d');
                $current->addDay();
            }
        }

        return array_values(array_unique($blockedDates));
    }

    /**
     * Manually block dates by the owner.
     */
    public function blockDates(Listing $listing, User $owner, string $start, string $end, ?string $reason = null): AvailabilityBlock
    {
        // First, check if there's any active booking in this period
        $hasBookingConflict = Booking::where('listing_id', $listing->id)
            ->whereIn('status', ['pending', 'confirmed', 'active'])
            ->where(function ($query) use ($start, $end) {
                $query->where('check_in_date', '<', $end)
                      ->where('check_out_date', '>', $start);
            })
            ->exists();

        if ($hasBookingConflict) {
            throw new \Exception('Cannot block dates because there is an existing booking in this period.');
        }

        return AvailabilityBlock::create([
            'listing_id' => $listing->id,
            'blocked_by_user_id' => $owner->id,
            'start_date' => $start,
            'end_date' => $end,
            'reason' => $reason,
        ]);
    }

    /**
     * Unblock manually blocked dates.
     */
    public function unblockDates(AvailabilityBlock $block): void
    {
        $block->delete();
    }
}
