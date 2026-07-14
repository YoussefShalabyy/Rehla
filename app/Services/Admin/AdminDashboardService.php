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
        $totalBookings = Booking::count();
        
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
            
        // Calculate gross sales (total_amount_cents) from completed/confirmed bookings
        $grossSalesCents = Booking::whereIn('status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->sum('total_amount_cents');

        // Profit from Cars
        $profitFromCarsCents = Booking::whereIn('bookings.status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->join('listings', 'bookings.listing_id', '=', 'listings.id')
            ->where('listings.type', 'car')
            ->sum('bookings.platform_fee_cents');

        // Profit from Properties
        $profitFromPropertiesCents = Booking::whereIn('bookings.status', [BookingStatus::Confirmed, BookingStatus::Completed])
            ->join('listings', 'bookings.listing_id', '=', 'listings.id')
            ->where('listings.type', 'property')
            ->sum('bookings.platform_fee_cents');

        return [
            'total_users'                => $totalUsers,
            'total_bookings'             => $totalBookings,
            'listings_by_status'         => $listingsByStatus,
            'bookings_by_status'         => $bookingsByStatus,
            'total_revenue_cents'        => (int) $totalRevenueCents,
            'gross_sales_cents'          => (int) $grossSalesCents,
            'profit_from_cars_cents'       => (int) $profitFromCarsCents,
            'profit_from_properties_cents' => (int) $profitFromPropertiesCents,
        ];
    }

    public function updateUserStatus(User $user, string $status): User
    {
        // Status can be 'active' or 'suspended'
        $user->update(['status' => $status]);
        return $user;
    }
}
