<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\BookingStatus;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\PaymentStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EndToEndHappyPathTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_platform_happy_path_scenario(): void
    {
        Storage::fake('local');

        // ---------------------------------------------------------
        // 1. Admin Creates & Publishes a Listing
        // ---------------------------------------------------------
        $admin = User::factory()->admin()->create();
        \Laravel\Sanctum\Sanctum::actingAs($admin);

        $listingResponse = $this->postJson('/api/v1/admin/listings', [
            'title'                 => 'Luxury Villa in Cairo',
            'description'           => 'A very nice place to stay.',
            'type'                  => ListingType::Property->value,
            'property_type'         => 'villa',
            'address'               => '123 Nile St',
            'country'               => 'Egypt',
            'city'                  => 'Cairo',
            'latitude'              => 30.0444,
            'longitude'             => 31.2357,
            'base_price_cents'      => 50000,
            'cleaning_fee_cents'    => 10000,
            'extra_guest_fee_cents' => 5000,
            'max_guests'            => 6,
            'is_instant_bookable'   => false,
        ]);
        $listingResponse->assertStatus(201);
        $listingUuid = $listingResponse->json('data.uuid');

        // Admin-created listings default to published
        $this->assertDatabaseHas('listings', [
            'uuid'   => $listingUuid,
            'status' => ListingStatus::Active->value,
        ]);

        // Admin uploads an image
        $this->postJson("/api/v1/admin/listings/{$listingUuid}/media", [
            'file' => UploadedFile::fake()->create('villa.jpg', 100, 'image/jpeg'),
        ])->assertStatus(201);

        // ---------------------------------------------------------
        // 2. Customer Registration & Searching
        // ---------------------------------------------------------
        $customerResponse = $this->postJson('/api/v1/auth/register', [
            'name'                  => 'Customer Ahmed',
            'email'                 => 'customer@rehla.com',
            'password'              => 'password123',
            'password_confirmation' => 'password123',
            'device_name'           => 'test_device',
        ]);
        $customerResponse->assertStatus(201);
        $customer = User::where('email', 'customer@rehla.com')->first();
        \Laravel\Sanctum\Sanctum::actingAs($customer);

        // Search for listing
        $searchResponse = $this->getJson('/api/v1/listings?city=Cairo&type=property');
        $searchResponse->assertStatus(200);
        $this->assertCount(1, $searchResponse->json('data'));
        $this->assertEquals($listingUuid, $searchResponse->json('data.0.uuid'));

        // ---------------------------------------------------------
        // 3. Booking Creation
        // ---------------------------------------------------------
        $checkIn  = now()->addDays(5)->format('Y-m-d');
        $checkOut = now()->addDays(8)->format('Y-m-d'); // 3 nights

        $bookingResponse = $this->postJson('/api/v1/bookings', [
            'listing_uuid'  => $listingUuid,
            'check_in_date' => $checkIn,
            'check_out_date'=> $checkOut,
            'guests_count'  => 2,
        ]);
        $bookingResponse->assertStatus(201);
        $bookingUuid = $bookingResponse->json('data.uuid');
        $bookingId   = Booking::where('uuid', $bookingUuid)->first()->id;

        $this->assertEquals(BookingStatus::Pending->value, $bookingResponse->json('data.status'));

        // ---------------------------------------------------------
        // 4. Payment Initiation
        // ---------------------------------------------------------
        $paymentResponse = $this->postJson('/api/v1/payments', [
            'booking_uuid' => $bookingUuid,
            'gateway'      => 'null_adapter',
        ]);
        $paymentResponse->assertStatus(201);

        // ---------------------------------------------------------
        // 5. Webhook Confirming Payment
        // ---------------------------------------------------------
        $payment = Payment::where('booking_id', $bookingId)->first();
        $this->assertNotNull($payment);

        $webhookResponse = $this->postJson('/api/v1/webhooks/paymob?hmac=valid-signature', [
            'obj' => [
                'order'          => ['id' => $payment->gateway_transaction_id],
                'success'        => true,
                'amount_cents'   => 1000000,
                'is_voided'      => false,
                'is_refunded'    => false,
                'error_occured'  => false,
            ],
        ]);
        $webhookResponse->assertStatus(200);

        $this->assertDatabaseHas('payments', [
            'id'     => $payment->id,
            'status' => PaymentStatus::Paid->value,
        ]);
        $this->assertDatabaseHas('bookings', [
            'id'     => $bookingId,
            'status' => BookingStatus::Confirmed->value,
        ]);

        // ---------------------------------------------------------
        // 6. Simulate Booking Completion
        // ---------------------------------------------------------
        Booking::find($bookingId)->update(['status' => BookingStatus::Completed]);

        // ---------------------------------------------------------
        // 7. Customer Leaves a Review
        // ---------------------------------------------------------
        \Laravel\Sanctum\Sanctum::actingAs($customer);
        $reviewResponse = $this->postJson('/api/v1/reviews', [
            'booking_uuid' => $bookingUuid,
            'rating'       => 5,
            'comment'      => 'Amazing stay! Very clean and exactly as described.',
        ]);
        $reviewResponse->assertStatus(201);
        $this->assertEquals(5, $reviewResponse->json('data.rating'));

        // Admin approves the review
        $reviewUuid = $reviewResponse->json('data.uuid');
        \Laravel\Sanctum\Sanctum::actingAs($admin);
        $this->putJson("/api/v1/admin/reviews/{$reviewUuid}/moderate", [
            'status' => ReviewStatus::Approved->value,
        ])->assertStatus(200);

        // Admin replies to the review
        $this->postJson("/api/v1/admin/reviews/{$reviewUuid}/reply", [
            'reply' => 'Thank you for your kind words!',
        ])->assertStatus(200);

        // Review appears publicly on listing
        $listingReviewsResponse = $this->getJson("/api/v1/listings/{$listingUuid}/reviews");
        $listingReviewsResponse->assertStatus(200);
        $this->assertCount(1, $listingReviewsResponse->json('data'));
        $this->assertEquals(5, $listingReviewsResponse->json('data.0.rating'));

        // E2E COMPLETE!
        $this->assertTrue(true);
    }
}
