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

    public function test_complete_platform_happy_path_scenario()
    {
        Storage::fake('cloudinary');

        // ---------------------------------------------------------
        // 1. Provider Registration & Listing Creation
        // ---------------------------------------------------------
        $providerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Provider Youssef',
            'email' => 'provider@vistastay.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::Provider->value,
            'device_name' => 'test_device',
        ]);
        $providerResponse->assertStatus(201);
        $providerToken = $providerResponse->json('data.token');
        $provider = User::where('email', 'provider@vistastay.com')->first();

        // Provider adds a listing
        \Laravel\Sanctum\Sanctum::actingAs($provider);
        $listingResponse = $this->postJson('/api/v1/owner/listings', [
            'title' => 'Luxury Villa in Cairo',
            'description' => 'A very nice place to stay.',
            'type' => ListingType::Property->value,
            'property_type' => 'villa',
            'address' => '123 Nile St',
            'country' => 'Egypt',
            'city' => 'Cairo',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'base_price_cents' => 50000, // 500 EGP
            'cleaning_fee_cents' => 10000, // 100 EGP
            'extra_guest_fee_cents' => 5000, // 50 EGP
            'max_guests' => 6,
            'is_instant_bookable' => false,
        ]);
        $listingResponse->assertStatus(201);
        $listingUuid = $listingResponse->json('data.uuid');
        $this->assertEquals(ListingStatus::Pending->value, $listingResponse->json('data.status'));

        // Provider uploads an image
        $file = UploadedFile::fake()->image('villa.jpg');
        $this->postJson("/api/v1/owner/listings/{$listingUuid}/media", [
            'file' => $file,
            'is_primary' => true,
        ])->assertStatus(201);

        // ---------------------------------------------------------
        // 2. Admin Approval
        // ---------------------------------------------------------
        $admin = User::factory()->admin()->create();
        \Laravel\Sanctum\Sanctum::actingAs($admin);

        $approveResponse = $this->postJson("/api/v1/admin/listings/{$listingUuid}/approve");
        $approveResponse->assertStatus(200);
        
        $this->assertDatabaseHas('listings', [
            'uuid' => $listingUuid,
            'status' => ListingStatus::Published->value,
        ]);

        // ---------------------------------------------------------
        // 3. Customer Registration & Searching
        // ---------------------------------------------------------
        $customerResponse = $this->postJson('/api/v1/auth/register', [
            'name' => 'Customer Ahmed',
            'email' => 'customer@vistastay.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => UserRole::Customer->value,
            'device_name' => 'test_device',
        ]);
        $customerResponse->assertStatus(201);
        $customer = User::where('email', 'customer@vistastay.com')->first();
        \Laravel\Sanctum\Sanctum::actingAs($customer);

        // Search for listing
        $searchResponse = $this->getJson('/api/v1/listings?city=Cairo&type=property');
        $searchResponse->assertStatus(200);
        $this->assertCount(1, $searchResponse->json('data'));
        $this->assertEquals($listingUuid, $searchResponse->json('data.0.uuid'));

        // ---------------------------------------------------------
        // 4. Booking Creation
        // ---------------------------------------------------------
        $checkIn = now()->addDays(5)->format('Y-m-d');
        $checkOut = now()->addDays(8)->format('Y-m-d'); // 3 nights

        $bookingResponse = $this->postJson('/api/v1/bookings', [
            'listing_uuid' => $listingUuid,
            'check_in_date' => $checkIn,
            'check_out_date' => $checkOut,
            'guests_count' => 2,
        ]);
        $bookingResponse->assertStatus(201);
        $bookingUuid = $bookingResponse->json('data.uuid');
        $bookingId = Booking::where('uuid', $bookingUuid)->first()->id;

        $this->assertEquals(BookingStatus::Pending->value, $bookingResponse->json('data.status'));

        // ---------------------------------------------------------
        // 5. Payment Initiation
        // ---------------------------------------------------------
        $paymentResponse = $this->postJson('/api/v1/payments', [
            'booking_uuid' => $bookingUuid,
            'gateway' => 'paymob',
        ]);
        $paymentResponse->assertStatus(201);
        $paymentUrl = $paymentResponse->json('data.checkout_url');
        $this->assertNotNull($paymentUrl);
        $this->assertStringContainsString('paymob', $paymentUrl);

        // ---------------------------------------------------------
        // 6. Webhook Confirming Payment
        // ---------------------------------------------------------
        $payment = Payment::where('booking_id', $bookingId)->first();
        $this->assertNotNull($payment);

        // NullPaymentAdapter expects 'valid-signature'
        $hmac = 'valid-signature';

        $webhookResponse = $this->postJson('/api/v1/webhooks/paymob?hmac=' . $hmac, [
            'obj' => [
                'order' => [
                    'id' => $payment->gateway_transaction_id,
                ],
                'success' => true,
                'amount_cents' => 1000000,
                'is_voided' => false,
                'is_refunded' => false,
                'error_occured' => false,
            ]
        ]);
        $webhookResponse->assertStatus(200);

        // Verify Payment is Succeeded and Booking is Confirmed
        $this->assertDatabaseHas('payments', [
            'id' => $payment->id,
            'status' => PaymentStatus::Paid->value,
        ]);
        $this->assertDatabaseHas('bookings', [
            'id' => $bookingId,
            'status' => BookingStatus::Confirmed->value,
        ]);

        // ---------------------------------------------------------
        // 7. Simulating Booking Completion
        // ---------------------------------------------------------
        // Fast forward in DB since we can't travel time in the webhook easily here
        $booking = Booking::find($bookingId);
        $booking->update(['status' => BookingStatus::Completed]);

        // ---------------------------------------------------------
        // 8. Customer Leaves a Review
        // ---------------------------------------------------------
        \Laravel\Sanctum\Sanctum::actingAs($customer);
        $reviewResponse = $this->postJson('/api/v1/reviews', [
            'booking_uuid' => $bookingUuid,
            'rating' => 5,
            'comment' => 'Amazing stay! Very clean and exactly as described.',
        ]);
        $reviewResponse->assertStatus(201);
        $this->assertEquals(5, $reviewResponse->json('data.rating'));

        // Admin approves the review
        $reviewUuid = $reviewResponse->json('data.uuid');
        \Laravel\Sanctum\Sanctum::actingAs($admin);
        $this->putJson("/api/v1/admin/reviews/{$reviewUuid}/moderate", [
            'status' => ReviewStatus::Approved->value,
        ])->assertStatus(200);

        // Check if review appears on the listing
        $listingReviewsResponse = $this->getJson("/api/v1/listings/{$listingUuid}/reviews");
        $listingReviewsResponse->assertStatus(200);
        $this->assertCount(1, $listingReviewsResponse->json('data'));
        $this->assertEquals(5, $listingReviewsResponse->json('data.0.rating'));

        // ---------------------------------------------------------
        // E2E COMPLETE!
        // ---------------------------------------------------------
        $this->assertTrue(true); // If we made it here, everything works!
    }
}
