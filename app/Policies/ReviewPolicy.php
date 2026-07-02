<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Booking;
use App\Models\Review;
use App\Models\User;
use App\Enums\BookingStatus;

class ReviewPolicy
{
    /**
     * Determine if the user can create a review for a booking.
     */
    public function create(User $user, Booking $booking): bool
    {
        return $booking->customer_id === $user->id 
            && $booking->status === BookingStatus::Completed;
    }

    /**
     * Determine if the user can reply to a review.
     */
    public function reply(User $user, Review $review): bool
    {
        // Load the listing if not already loaded to check owner_id
        $review->loadMissing('listing');
        
        return $review->listing->owner_id === $user->id;
    }
}
