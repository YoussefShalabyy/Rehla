<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymobWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_webhook_confirms_payment_and_booking()
    {
        config(['payment.paymob.hmac_secret' => 'test_secret']);

        $payment = Payment::factory()->create([
            'status' => 'pending',
            'gateway_transaction_id' => '123456',
        ]);
        
        $booking = $payment->booking;
        $booking->update(['status' => 'pending', 'payment_status' => 'pending']);

        $payload = [
            'obj' => [
                'order' => ['id' => '123456'],
                'success' => true,
            ]
        ];

        // Mock the PaymentGatewayInterface to bypass actual HMAC string matching
        $mock = \Mockery::mock(\App\Interfaces\PaymentGatewayInterface::class);
        $mock->shouldReceive('verifyWebhook')->once()->andReturn(true);
        $this->app->instance(\App\Interfaces\PaymentGatewayInterface::class, $mock);

        $this->withoutExceptionHandling();
        $response = $this->postJson("/api/v1/webhooks/paymob?hmac=valid_signature", $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => 'paid',
        ]);

        $this->assertDatabaseHas('bookings', [
            'id' => $booking->id,
            'status' => 'confirmed',
            'payment_status' => 'paid',
        ]);
    }

    public function test_invalid_signature_rejects_webhook()
    {
        $mock = \Mockery::mock(\App\Interfaces\PaymentGatewayInterface::class);
        $mock->shouldReceive('verifyWebhook')->once()->andReturn(false);
        $this->app->instance(\App\Interfaces\PaymentGatewayInterface::class, $mock);

        $response = $this->postJson("/api/v1/webhooks/paymob?hmac=invalid_signature", [
            'obj' => ['order' => ['id' => '123']]
        ]);

        $response->assertStatus(400);
        $response->assertJsonPath('success', false);
    }
}
