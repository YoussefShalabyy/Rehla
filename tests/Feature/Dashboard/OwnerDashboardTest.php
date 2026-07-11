<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use App\Models\Listing;
use App\Models\Booking;
use App\Enums\BookingStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Admin Dashboard Test
 * Owner dashboard has been removed — only admin dashboard exists.
 */
class OwnerDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin    = User::factory()->create(['role' => UserRole::Admin]);
        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
    }

    public function test_admin_can_view_dashboard_stats(): void
    {
        $listing = Listing::factory()->create(['created_by' => $this->admin->id]);
        Booking::factory()->create([
            'listing_id'         => $listing->id,
            'status'             => BookingStatus::Completed,
            'total_amount_cents' => 10000,
            'platform_fee_cents' => 1000,
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/admin/dashboard/stats');

        $response->assertOk();
    }

    public function test_customer_cannot_access_admin_routes(): void
    {
        $response = $this->actingAs($this->customer, 'sanctum')->getJson('/api/v1/admin/dashboard/stats');
        $response->assertForbidden();
    }

    public function test_owner_routes_no_longer_exist(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/owner/dashboard/stats');
        $response->assertNotFound();
    }
}
