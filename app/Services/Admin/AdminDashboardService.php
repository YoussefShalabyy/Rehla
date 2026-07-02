<?php

declare(strict_types=1);

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Listing;
use App\Models\Booking;
use App\Enums\BookingStatus;
use App\Enums\ListingStatus;

class AdminDashboardService
{
    public function getStats(): array
    {
        $totalUsers = User::count();
        
        $listingsByStatus = Listing::toBase()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $bookingsByStatus = Booking::toBase()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        // Calculate total revenue from completed/confirmed bookings platform fees
        $totalRevenueCents = Booking::whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->sum('platform_fee_cents');

        return [
            'total_users'             => $totalUsers,
            'listings_by_status'      => $listingsByStatus,
            'bookings_by_status'      => $bookingsByStatus,
            'total_revenue_cents'     => (int) $totalRevenueCents,
            'pending_approvals_count' => $listingsByStatus[ListingStatus::Pending->value] ?? 0,
        ];
    }

    public function updateUserStatus(User $user, string $status): User
    {
        // Status can be 'active' or 'suspended'
        $user->update(['status' => $status]);
        return $user;
    }
}
