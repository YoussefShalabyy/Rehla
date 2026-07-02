<?php

declare(strict_types=1);

namespace App\Services\Owner;

use App\Models\User;
use App\Models\Booking;
use App\Enums\BookingStatus;
use Illuminate\Support\Facades\DB;

class OwnerDashboardService
{
    public function getStats(User $owner): array
    {
        $listingIds = $owner->listings()->pluck('id');

        if ($listingIds->isEmpty()) {
            return [
                'total_bookings'      => 0,
                'bookings_by_status'  => [],
                'total_revenue_cents' => 0,
                'total_listings'      => 0,
            ];
        }

        $bookingsByStatus = Booking::toBase()
            ->whereIn('listing_id', $listingIds)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $totalRevenueCents = Booking::whereIn('listing_id', $listingIds)
            ->whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->sum(DB::raw('total_amount_cents - platform_fee_cents'));

        return [
            'total_bookings'      => array_sum($bookingsByStatus),
            'bookings_by_status'  => $bookingsByStatus,
            'total_revenue_cents' => (int) $totalRevenueCents,
            'total_listings'      => $listingIds->count(),
        ];
    }
}
