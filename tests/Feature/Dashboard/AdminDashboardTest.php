<?php

namespace Tests\Feature\Dashboard;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AdminDashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->customer = User::factory()->create(['role' => 'customer']);
    }

    public function test_admin_can_view_dashboard_stats()
    {
        $response = $this->actingAs($this->admin, 'sanctum')->getJson('/api/v1/admin/dashboard/stats');

        $response->assertOk()
            ->assertJsonStructure(['data' => ['total_users', 'total_bookings', 'listings_by_status', 'bookings_by_status', 'total_revenue_cents', 'gross_sales_cents']]);
    }

    public function test_non_admin_cannot_access_admin_routes()
    {
        $response = $this->actingAs($this->customer, 'sanctum')->getJson('/api/v1/admin/dashboard/stats');
        $response->assertForbidden();
    }

    public function test_admin_can_suspend_user()
    {
        $response = $this->actingAs($this->admin, 'sanctum')->putJson("/api/v1/admin/users/{$this->customer->uuid}/status", [
            'status' => 'suspended',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.status', 'suspended');

        $this->assertEquals('suspended', $this->customer->fresh()->status->value);
    }

    public function test_admin_can_update_platform_settings()
    {
        DB::table('platform_settings')->insert([
            'key' => 'platform_fee_percentage',
            'value' => '10',
            'type' => 'integer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')->putJson('/api/v1/admin/settings/platform_fee_percentage', [
            'value' => '15',
        ]);

        $response->assertOk();
        $this->assertEquals('15', DB::table('platform_settings')->where('key', 'platform_fee_percentage')->value('value'));
    }

    public function test_admin_cannot_update_nonexistent_setting()
    {
        $response = $this->actingAs($this->admin, 'sanctum')->putJson('/api/v1/admin/settings/does_not_exist', [
            'value' => '15',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }
}
