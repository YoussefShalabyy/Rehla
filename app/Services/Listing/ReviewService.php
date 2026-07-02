<?php

declare(strict_types=1);

namespace App\Services\Listing;

use App\DTOs\Review\CreateReviewDTO;
use App\Enums\BookingStatus;
use App\Enums\ReviewStatus;
use App\Exceptions\UnauthorizedActionException;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\Review;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ReviewService
{
    /**
     * Create a new review.
     * Enforces:
     * - booking.status === completed
     * - no existing review for this booking
     * - reviewer === booking.customer
     */
    public function createReview(CreateReviewDTO $dto, User $customer): Review
    {
        $booking = Booking::where('uuid', $dto->bookingUuid)->firstOrFail();

        if ($booking->customer_id !== $customer->id) {
            throw new UnauthorizedActionException('You can only review your own bookings.');
        }

        if ($booking->status !== BookingStatus::Completed) {
            throw new HttpException(422, 'You can only review a booking after it has been completed.');
        }

        if (Review::where('booking_id', $booking->id)->exists()) {
            throw new HttpException(422, 'A review for this booking already exists.');
        }

        return Review::create([
            'booking_id'  => $booking->id,
            'reviewer_id' => $customer->id,
            'listing_id'  => $booking->listing_id,
            'rating'      => $dto->rating,
            'comment'     => $dto->comment,
            'status'      => ReviewStatus::Approved, // Default to approved in MVP unless configured otherwise
        ]);
    }

    /**
     * Owner replies to a review.
     * Enforces:
     * - owner of listing === user
     * - reply not already set
     */
    public function ownerReply(Review $review, string $reply, User $owner): Review
    {
        $review->loadMissing('listing');

        if ($review->listing->owner_id !== $owner->id) {
            throw new UnauthorizedActionException('You can only reply to reviews on your own listings.');
        }

        if (!empty($review->owner_reply)) {
            throw new HttpException(422, 'You have already replied to this review.');
        }

        $review->update([
            'owner_reply'    => $reply,
            'owner_reply_at' => now(),
        ]);

        return $review;
    }

    /**
     * Admin moderates a review (e.g. hides it).
     */
    public function moderate(Review $review, ReviewStatus $status, User $admin): Review
    {
        $review->update(['status' => $status]);
        return $review;
    }

    /**
     * Get paginated approved reviews for a listing.
     */
    public function getListingReviews(Listing $listing, int $perPage = 20): LengthAwarePaginator
    {
        return Review::with('reviewer')
            ->where('listing_id', $listing->id)
            ->where('status', ReviewStatus::Approved)
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Get average rating for a listing (computed from approved reviews only).
     */
    public function getAverageRating(Listing $listing): float
    {
        $average = Review::where('listing_id', $listing->id)
            ->where('status', ReviewStatus::Approved)
            ->avg('rating');

        return $average ? round((float) $average, 1) : 0.0;
    }
}
