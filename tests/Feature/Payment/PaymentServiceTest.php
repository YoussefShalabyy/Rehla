<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Enums\PaymentGateway;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\PlatformSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private Booking $booking;

    protected function setUp(): void
    {
        parent::setUp();
        
        PlatformSetting::set('platform_fee_percentage', 10);
        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
        $listing = Listing::factory()->create();

        $this->booking = Booking::factory()->create([
            'listing_id' => $listing->id,
            'customer_id' => $this->customer->id,
            'status' => 'pending',
            'payment_status' => 'pending',
            'total_amount_cents' => 5000,
        ]);
    }

    public function test_customer_can_initiate_payment_for_their_booking()
    {
        $this->withoutExceptionHandling();
        $response = $this->actingAs($this->customer)->postJson('/api/v1/payments', [
            'booking_uuid' => $this->booking->uuid,
            'gateway' => PaymentGateway::Paymob->value,
        ]);

        $response->assertStatus(201);
        $response->assertJsonPath('success', true);
        $response->assertJsonStructure([
            'data' => [
                'payment' => [
                    'uuid',
                    'amount_cents',
                    'status',
                ],
                'checkout_url'
            ]
        ]);

        $this->assertDatabaseHas('payments', [
            'booking_id' => $this->booking->id,
            'status' => 'pending',
            'gateway' => 'paymob'
        ]);
    }

    public function test_cannot_initiate_payment_for_confirmed_booking()
    {
        $this->booking->update(['status' => 'confirmed', 'payment_status' => 'paid']);

        $response = $this->actingAs($this->customer)->postJson('/api/v1/payments', [
            'booking_uuid' => $this->booking->uuid,
            'gateway' => PaymentGateway::Paymob->value,
        ]);

        $response->assertStatus(422); // Unprocessable, already paid
    }

    public function test_another_customer_cannot_pay_for_booking()
    {
        $anotherCustomer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($anotherCustomer)->postJson('/api/v1/payments', [
            'booking_uuid' => $this->booking->uuid,
            'gateway' => PaymentGateway::Paymob->value,
        ]);

        $response->assertStatus(403);
    }
}
