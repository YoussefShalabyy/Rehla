<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Models\Listing;
use App\Models\Booking;
use App\Enums\BookingStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = User::factory()->create(['role' => 'provider']);
        $this->customer = User::factory()->create(['role' => 'customer']);
    }

    public function test_owner_can_view_own_stats()
    {
        $listing = Listing::factory()->create(['owner_id' => $this->owner->id]);
        Booking::factory()->create([
            'listing_id' => $listing->id,
            'status' => BookingStatus::Completed,
            'total_amount_cents' => 10000,
            'platform_fee_cents' => 1000, // 9000 revenue
        ]);

        $response = $this->actingAs($this->owner, 'sanctum')->getJson('/api/v1/owner/dashboard/stats');

        $response->assertOk()
            ->assertJsonPath('data.total_bookings', 1)
            ->assertJsonPath('data.total_revenue_cents', 9000)
            ->assertJsonPath('data.total_listings', 1);
    }

    public function test_non_owner_cannot_access_owner_routes()
    {
        $response = $this->actingAs($this->customer, 'sanctum')->getJson('/api/v1/owner/dashboard/stats');
        $response->assertForbidden();
    }
}
