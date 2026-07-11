<?php

namespace Tests\Feature\Notification;

use App\Models\User;
use App\Models\Booking;
use App\Models\Listing;
use App\Notifications\BookingConfirmedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NotificationApiTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->customer = User::factory()->create(['role' => 'customer']);
        $admin   = User::factory()->create(['role' => 'admin']);
        $listing = Listing::factory()->create(['created_by' => $admin->id]);
        
        $this->booking = Booking::factory()->create([
            'customer_id' => $this->customer->id,
            'listing_id'  => $listing->id,
        ]);

        // Send two notifications
        $this->customer->notify(new BookingConfirmedNotification($this->booking));
        $this->customer->notify(new BookingConfirmedNotification($this->booking));
    }

    public function test_customer_can_list_notifications()
    {
        $response = $this->actingAs($this->customer, 'sanctum')->getJson('/api/v1/notifications');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'type', 'title', 'message', 'read_at', 'created_at']]]);
            
        $this->assertCount(2, $response->json('data'));
    }

    public function test_customer_can_get_unread_count()
    {
        $response = $this->actingAs($this->customer, 'sanctum')->getJson('/api/v1/notifications/unread');

        $response->assertOk()
            ->assertJsonPath('data.count', 2);
    }

    public function test_customer_can_mark_one_as_read()
    {
        $notification = $this->customer->notifications()->first();

        $response = $this->actingAs($this->customer, 'sanctum')->putJson("/api/v1/notifications/{$notification->id}/read");

        $response->assertOk();
        $this->assertNotNull($notification->fresh()->read_at);
        $this->assertEquals(1, $this->customer->unreadNotifications()->count());
    }

    public function test_customer_can_mark_all_as_read()
    {
        $response = $this->actingAs($this->customer, 'sanctum')->putJson('/api/v1/notifications/read-all');

        $response->assertOk();
        $this->assertEquals(0, $this->customer->unreadNotifications()->count());
    }
}
