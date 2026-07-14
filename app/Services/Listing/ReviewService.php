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

        $review = Review::create([
            'booking_id'  => $booking->id,
            'reviewer_id' => $customer->id,
            'listing_id'  => $booking->listing_id,
            'rating'      => $dto->rating,
            'comment'     => $dto->comment,
            'status'      => ReviewStatus::Approved,
        ]);

        // Recalculate the listing's real average rating
        $this->recalculateListingRating($booking->listing);

        return $review;
    }

    /**
     * Create a new review from the Admin dashboard.
     */
    public function createAdminReview(Listing $listing, int $rating, ?string $comment, ?string $reviewerName): Review
    {
        $review = Review::create([
            'listing_id'    => $listing->id,
            'rating'        => $rating,
            'comment'       => $comment,
            'reviewer_name' => $reviewerName,
            'status'        => ReviewStatus::Approved,
        ]);

        $this->recalculateListingRating($listing);

        return $review;
    }

    /**
     * Admin replies to a review from the dashboard.
     */
    public function adminReply(Review $review, string $reply, User $admin): Review
    {
        if (!empty($review->owner_reply)) {
            throw new HttpException(422, 'A reply has already been added to this review.');
        }

        $review->update([
            'owner_reply'    => $reply,
            'owner_reply_at' => now(),
        ]);

        return $review;
    }

    /**
     * Admin moderates a review (approve / hide).
     */
    public function moderate(Review $review, ReviewStatus $status, User $admin): Review
    {
        $review->update(['status' => $status]);

        // Recalculate listing rating since approved reviews changed
        $this->recalculateListingRating($review->listing);

        return $review;
    }

    /**
     * Recalculate and persist the real average_rating and total_reviews
     * on a listing based on approved reviews.
     */
    public function recalculateListingRating(Listing $listing): void
    {
        $stats = Review::where('listing_id', $listing->id)
            ->where('status', ReviewStatus::Approved)
            ->selectRaw('COUNT(*) as total, AVG(rating) as average')
            ->first();

        $listing->update([
            'average_rating' => $stats->average ? round((float) $stats->average, 2) : 0.00,
            'total_reviews'  => (int) $stats->total,
        ]);
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
}
