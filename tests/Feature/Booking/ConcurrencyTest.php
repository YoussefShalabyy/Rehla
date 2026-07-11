<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\PlatformSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_prevents_double_booking_under_sequential_requests(): void
    {
        PlatformSetting::set('platform_fee_percentage', 10);

        $admin     = User::factory()->create(['role' => UserRole::Admin]);
        $customer1 = User::factory()->create(['role' => UserRole::Customer]);
        $customer2 = User::factory()->create(['role' => UserRole::Customer]);

        $listing = Listing::factory()->create([
            'created_by'       => $admin->id,
            'status'           => ListingStatus::Published,
            'base_price_cents' => 1000,
        ]);

        $checkIn  = Carbon::today()->addDays(1)->format('Y-m-d');
        $checkOut = Carbon::today()->addDays(3)->format('Y-m-d');

        // First booking must succeed
        $this->actingAs($customer1)->postJson('/api/v1/bookings', [
            'listing_uuid'  => $listing->uuid,
            'check_in_date' => $checkIn,
            'check_out_date'=> $checkOut,
            'guests_count'  => 1,
        ])->assertStatus(201);

        // Second booking on same dates must be rejected with 409
        $this->actingAs($customer2)->postJson('/api/v1/bookings', [
            'listing_uuid'  => $listing->uuid,
            'check_in_date' => $checkIn,
            'check_out_date'=> $checkOut,
            'guests_count'  => 1,
        ])->assertStatus(409);

        // Assert only 1 booking was created
        $this->assertEquals(1, Booking::where('listing_id', $listing->id)->count());
    }

    public function test_allows_non_overlapping_bookings_for_same_listing(): void
    {
        PlatformSetting::set('platform_fee_percentage', 10);

        $admin     = User::factory()->create(['role' => UserRole::Admin]);
        $customer1 = User::factory()->create(['role' => UserRole::Customer]);
        $customer2 = User::factory()->create(['role' => UserRole::Customer]);

        $listing = Listing::factory()->create([
            'created_by'       => $admin->id,
            'status'           => ListingStatus::Published,
            'base_price_cents' => 1000,
        ]);

        // Non-overlapping dates
        $this->actingAs($customer1)->postJson('/api/v1/bookings', [
            'listing_uuid'  => $listing->uuid,
            'check_in_date' => Carbon::today()->addDays(1)->format('Y-m-d'),
            'check_out_date'=> Carbon::today()->addDays(3)->format('Y-m-d'),
            'guests_count'  => 1,
        ])->assertStatus(201);

        $this->actingAs($customer2)->postJson('/api/v1/bookings', [
            'listing_uuid'  => $listing->uuid,
            'check_in_date' => Carbon::today()->addDays(5)->format('Y-m-d'),
            'check_out_date'=> Carbon::today()->addDays(7)->format('Y-m-d'),
            'guests_count'  => 1,
        ])->assertStatus(201);

        $this->assertEquals(2, Booking::where('listing_id', $listing->id)->count());
    }
}
