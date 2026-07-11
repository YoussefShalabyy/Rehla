<?php

namespace Tests\Feature\Review;

use App\Enums\BookingStatus;
use App\Enums\ReviewStatus;
use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User    $customer;
    private User    $admin;
    private Listing $listing;
    private Booking $completedBooking;
    private Booking $pendingBooking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => UserRole::Customer]);
        $this->admin    = User::factory()->create(['role' => UserRole::Admin]);

        $this->listing = Listing::factory()->create([
            'created_by' => $this->admin->id,
            'status'     => 'published',
        ]);

        $this->completedBooking = Booking::factory()->create([
            'customer_id' => $this->customer->id,
            'listing_id'  => $this->listing->id,
            'status'      => BookingStatus::Completed,
        ]);

        $this->pendingBooking = Booking::factory()->create([
            'customer_id' => $this->customer->id,
            'listing_id'  => $this->listing->id,
            'status'      => BookingStatus::Pending,
        ]);
    }

    public function test_customer_can_review_completed_booking(): void
    {
        $response = $this->actingAs($this->customer)->postJson('/api/v1/reviews', [
            'booking_uuid' => $this->completedBooking->uuid,
            'rating'       => 5,
            'comment'      => 'Great stay!',
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.rating', 5)
            ->assertJsonPath('data.comment', 'Great stay!');

        $this->assertDatabaseHas('reviews', [
            'booking_id' => $this->completedBooking->id,
            'rating'     => 5,
        ]);
    }

    public function test_cannot_review_non_completed_booking(): void
    {
        $response = $this->actingAs($this->customer)->postJson('/api/v1/reviews', [
            'booking_uuid' => $this->pendingBooking->uuid,
            'rating'       => 4,
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_leave_two_reviews_for_same_booking(): void
    {
        Review::create([
            'booking_id'  => $this->completedBooking->id,
            'reviewer_id' => $this->customer->id,
            'listing_id'  => $this->listing->id,
            'rating'      => 4,
            'status'      => ReviewStatus::Approved,
        ]);

        $response = $this->actingAs($this->customer)->postJson('/api/v1/reviews', [
            'booking_uuid' => $this->completedBooking->uuid,
            'rating'       => 5,
        ]);

        $response->assertStatus(422);
    }

    public function test_non_booking_owner_cannot_review(): void
    {
        $otherCustomer = User::factory()->create(['role' => UserRole::Customer]);

        $response = $this->actingAs($otherCustomer)->postJson('/api/v1/reviews', [
            'booking_uuid' => $this->completedBooking->uuid,
            'rating'       => 5,
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_reply_to_review(): void
    {
        $review = Review::create([
            'booking_id'  => $this->completedBooking->id,
            'reviewer_id' => $this->customer->id,
            'listing_id'  => $this->listing->id,
            'rating'      => 4,
            'status'      => ReviewStatus::Approved,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/v1/admin/reviews/{$review->uuid}/reply", [
            'reply' => 'Thank you for your feedback!',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.owner_reply', 'Thank you for your feedback!');

        $this->assertDatabaseHas('reviews', [
            'id'          => $review->id,
            'owner_reply' => 'Thank you for your feedback!',
        ]);
    }

    public function test_admin_cannot_reply_twice(): void
    {
        $review = Review::create([
            'booking_id'  => $this->completedBooking->id,
            'reviewer_id' => $this->customer->id,
            'listing_id'  => $this->listing->id,
            'rating'      => 4,
            'owner_reply' => 'First reply',
            'status'      => ReviewStatus::Approved,
        ]);

        $response = $this->actingAs($this->admin)->postJson("/api/v1/admin/reviews/{$review->uuid}/reply", [
            'reply' => 'Second reply',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_moderate_reviews(): void
    {
        $review = Review::create([
            'booking_id'  => $this->completedBooking->id,
            'reviewer_id' => $this->customer->id,
            'listing_id'  => $this->listing->id,
            'rating'      => 4,
            'status'      => ReviewStatus::Pending,
        ]);

        $response = $this->actingAs($this->admin)->putJson("/api/v1/admin/reviews/{$review->uuid}/moderate", [
            'status' => 'hidden',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('reviews', [
            'id'     => $review->id,
            'status' => 'hidden',
        ]);
    }

    public function test_hidden_reviews_not_returned_in_public_listing(): void
    {
        Review::create([
            'booking_id'  => $this->completedBooking->id,
            'reviewer_id' => $this->customer->id,
            'listing_id'  => $this->listing->id,
            'rating'      => 4,
            'status'      => ReviewStatus::Hidden,
        ]);

        $response = $this->getJson("/api/v1/listings/{$this->listing->uuid}/reviews");

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');
    }
}
