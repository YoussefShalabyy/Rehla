<?php

declare(strict_types=1);

namespace Tests\Feature\Booking;

use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Models\AvailabilityBlock;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\PlatformSetting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $provider;
    private User $admin;
    private Listing $listing;

    protected function setUp(): void
    {
        parent::setUp();
        
        PlatformSetting::set('platform_fee_percentage', 10);

        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
        $this->provider = User::factory()->create(['role' => UserRole::Provider]);
        $this->admin = User::factory()->create(['role' => UserRole::Admin]);

        $this->listing = Listing::factory()->create([
            'owner_id' => $this->provider->id,
            'status' => ListingStatus::Published,
            'base_price_cents' => 1000,
            'cleaning_fee_cents' => 0,
            'extra_guest_fee_cents' => 0,
            'max_guests' => 4,
        ]);
    }

    public function test_customer_can_book_available_listing()
    {
        $response = $this->actingAs($this->customer)->postJson('/api/v1/bookings', [
            'listing_uuid' => $this->listing->uuid,
            'check_in_date' => Carbon::today()->addDays(1)->format('Y-m-d'),
            'check_out_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'guests_count' => 2,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('data.status', 'pending');
        
        // 2 nights * 1000 = 2000 base + 200 fee = 2200
        $response->assertJsonPath('data.total_amount_cents', 2200);
        $response->assertJsonStructure([
            'data' => [
                'uuid',
                'booking_reference',
                'pricing_breakdown',
            ]
        ]);
    }

    public function test_returns_409_when_dates_are_already_booked()
    {
        // First booking
        Booking::factory()->create([
            'listing_id' => $this->listing->id,
            'check_in_date' => Carbon::today()->addDays(1),
            'check_out_date' => Carbon::today()->addDays(5),
            'status' => 'confirmed'
        ]);

        // Attempt overlapping booking
        $response = $this->actingAs($this->customer)->postJson('/api/v1/bookings', [
            'listing_uuid' => $this->listing->uuid,
            'check_in_date' => Carbon::today()->addDays(2)->format('Y-m-d'),
            'check_out_date' => Carbon::today()->addDays(4)->format('Y-m-d'),
            'guests_count' => 1,
        ]);

        $response->assertStatus(409);
        $response->assertJsonPath('success', false);
    }

    public function test_returns_409_when_dates_are_manually_blocked()
    {
        AvailabilityBlock::create([
            'listing_id' => $this->listing->id,
            'blocked_by_user_id' => $this->provider->id,
            'start_date' => Carbon::today()->addDays(2),
            'end_date' => Carbon::today()->addDays(4),
        ]);

        // Attempt overlapping booking
        $response = $this->actingAs($this->customer)->postJson('/api/v1/bookings', [
            'listing_uuid' => $this->listing->uuid,
            'check_in_date' => Carbon::today()->addDays(1)->format('Y-m-d'),
            'check_out_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'guests_count' => 1,
        ]);

        $response->assertStatus(409);
    }

    public function test_returns_422_when_check_in_is_in_the_past()
    {
        $response = $this->actingAs($this->customer)->postJson('/api/v1/bookings', [
            'listing_uuid' => $this->listing->uuid,
            'check_in_date' => Carbon::today()->subDays(1)->format('Y-m-d'),
            'check_out_date' => Carbon::today()->addDays(1)->format('Y-m-d'),
            'guests_count' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_returns_422_when_check_out_is_before_check_in()
    {
        $response = $this->actingAs($this->customer)->postJson('/api/v1/bookings', [
            'listing_uuid' => $this->listing->uuid,
            'check_in_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'check_out_date' => Carbon::today()->addDays(1)->format('Y-m-d'),
            'guests_count' => 1,
        ]);

        $response->assertStatus(422);
    }

    public function test_customer_can_cancel_own_booking()
    {
        $booking = Booking::factory()->create([
            'listing_id' => $this->listing->id,
            'customer_id' => $this->customer->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($this->customer)->postJson("/api/v1/bookings/{$booking->uuid}/cancel", [
            'reason' => 'Changed my mind'
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'cancelled',
            'cancellation_reason' => 'Changed my mind'
        ]);
    }

    public function test_non_owner_cannot_cancel_booking()
    {
        $otherCustomer = User::factory()->create(['role' => UserRole::Customer]);
        $booking = Booking::factory()->create([
            'listing_id' => $this->listing->id,
            'customer_id' => $this->customer->id,
            'status' => 'pending'
        ]);

        $response = $this->actingAs($otherCustomer)->postJson("/api/v1/bookings/{$booking->uuid}/cancel");

        $response->assertStatus(403);
    }

    public function test_provider_cannot_create_a_booking()
    {
        $response = $this->actingAs($this->provider)->postJson('/api/v1/bookings', [
            'listing_uuid' => $this->listing->uuid,
            'check_in_date' => Carbon::today()->addDays(1)->format('Y-m-d'),
            'check_out_date' => Carbon::today()->addDays(3)->format('Y-m-d'),
            'guests_count' => 1,
        ]);

        $response->assertStatus(403);
    }

    public function test_availability_endpoint_returns_blocked_dates()
    {
        Booking::factory()->create([
            'listing_id' => $this->listing->id,
            'check_in_date' => Carbon::today()->addDays(1),
            'check_out_date' => Carbon::today()->addDays(3),
            'status' => 'confirmed'
        ]);

        $response = $this->getJson("/api/v1/listings/{$this->listing->uuid}/availability?month=" . date('Y-m'));

        $response->assertStatus(200);
        $blockedDates = $response->json('data.blocked_dates');
        $this->assertContains(Carbon::today()->addDays(1)->format('Y-m-d'), $blockedDates);
        $this->assertContains(Carbon::today()->addDays(2)->format('Y-m-d'), $blockedDates);
    }
}
