<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Listing;
use App\Models\Booking;
use App\Models\Review;
use App\Enums\UserRole;
use App\Enums\ListingStatus;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            return;
        }

        // Get or create an admin to own seeded listings
        $admin = User::where('role', UserRole::Admin->value)->first()
            ?? User::factory()->admin()->create();

        // Create 10 Customers
        $customers = User::factory(10)->customer()->create();

        // Create 10 Listings (mix of cars and properties) owned by admin
        $listings = collect();
        $listings = $listings->merge(
            Listing::factory(5)->published()->create(['created_by' => $admin->id])
        );
        $listings->push(
            Listing::factory()->car()->published()->create(['created_by' => $admin->id])
        );

        // Create 20 Bookings
        $bookings = collect();
        for ($i = 0; $i < 20; $i++) {
            $bookings->push(
                Booking::factory()->completed()->create([
                    'customer_id' => $customers->random()->id,
                    'listing_id'  => $listings->random()->id,
                ])
            );
        }

        // Create 15 Reviews
        for ($i = 0; $i < 15; $i++) {
            $booking = $bookings->random();
            Review::factory()->approved()->create([
                'booking_id'  => $booking->id,
                'reviewer_id' => $booking->customer_id,
                'listing_id'  => $booking->listing_id,
            ]);
        }
    }
}
