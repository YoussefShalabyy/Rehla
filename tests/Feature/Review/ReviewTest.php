<?php

namespace Tests\Feature\Review;

use App\Enums\BookingStatus;
use App\Enums\ReviewStatus;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReviewTest extends TestCase
{
    use RefreshDatabase;

    private User $customer;
    private User $owner;
    private User $admin;
    private Listing $listing;
    private Booking $completedBooking;
    private Booking $pendingBooking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = User::factory()->create(['role' => 'customer']);
        $this->owner = User::factory()->create(['role' => 'provider']);
        $this->admin = User::factory()->create(['role' => 'admin']);

        $this->listing = Listing::factory()->create([
            'owner_id' => $this->owner->id,
            'status'   => 'published',
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

    public function test_customer_can_review_completed_booking()
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

    public function test_cannot_review_non_completed_booking()
    {
        $response = $this->actingAs($this->customer)->postJson('/api/v1/reviews', [
            'booking_uuid' => $this->pendingBooking->uuid,
            'rating'       => 4,
        ]);

        $response->assertStatus(403);
    }

    public function test_cannot_leave_two_reviews_for_same_booking()
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

    public function test_non_booking_owner_cannot_review()
    {
        $otherCustomer = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($otherCustomer)->postJson('/api/v1/reviews', [
            'booking_uuid' => $this->completedBooking->uuid,
            'rating'       => 5,
        ]);

        $response->assertStatus(403);
    }

    public function test_owner_can_reply_to_review_on_own_listing()
    {
        $review = Review::create([
            'booking_id'  => $this->completedBooking->id,
            'reviewer_id' => $this->customer->id,
            'listing_id'  => $this->listing->id,
            'rating'      => 4,
            'status'      => ReviewStatus::Approved,
        ]);

        $response = $this->actingAs($this->owner)->postJson("/api/v1/owner/reviews/{$review->uuid}/reply", [
            'reply' => 'Thank you!',
        ]);

        $response->assertStatus(200)
                 ->assertJsonPath('data.owner_reply', 'Thank you!');

        $this->assertDatabaseHas('reviews', [
            'id'          => $review->id,
            'owner_reply' => 'Thank you!',
        ]);
    }

    public function test_owner_cannot_reply_twice()
    {
        $review = Review::create([
            'booking_id'     => $this->completedBooking->id,
            'reviewer_id'    => $this->customer->id,
            'listing_id'     => $this->listing->id,
            'rating'         => 4,
            'owner_reply'    => 'First reply',
            'status'         => ReviewStatus::Approved,
        ]);

        $response = $this->actingAs($this->owner)->postJson("/api/v1/owner/reviews/{$review->uuid}/reply", [
            'reply' => 'Second reply',
        ]);

        $response->assertStatus(422);
    }

    public function test_admin_can_approve_and_hide_reviews()
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

    public function test_hidden_reviews_not_returned_in_public_listing()
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

    public function test_average_rating_is_calculated_correctly()
    {
        // 1st review (Approved, 4)
        Review::create([
            'booking_id'  => $this->completedBooking->id,
            'reviewer_id' => $this->customer->id,
            'listing_id'  => $this->listing->id,
            'rating'      => 4,
            'status'      => ReviewStatus::Approved,
        ]);

        // 2nd review (Approved, 5)
        $booking2 = Booking::factory()->create([
            'customer_id' => $this->customer->id,
            'listing_id'  => $this->listing->id,
            'status'      => BookingStatus::Completed,
        ]);
        Review::create([
            'booking_id'  => $booking2->id,
            'reviewer_id' => $this->customer->id,
            'listing_id'  => $this->listing->id,
            'rating'      => 5,
            'status'      => ReviewStatus::Approved,
        ]);

        // 3rd review (Hidden, 1) -> shouldn't affect average
        $booking3 = Booking::factory()->create([
            'customer_id' => $this->customer->id,
            'listing_id'  => $this->listing->id,
            'status'      => BookingStatus::Completed,
        ]);
        Review::create([
            'booking_id'  => $booking3->id,
            'reviewer_id' => $this->customer->id,
            'listing_id'  => $this->listing->id,
            'rating'      => 1,
            'status'      => ReviewStatus::Hidden,
        ]);

        $response = $this->getJson("/api/v1/listings/{$this->listing->uuid}/reviews");

        $response->assertStatus(200)
                 ->assertJsonPath('meta.average_rating', 4.5);
    }
}
