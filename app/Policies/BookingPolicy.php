<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\User;

class BookingPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Booking $booking): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($booking->customer_id === $user->id) {
            return true; // The customer who booked it
        }

        if ($booking->listing->owner_id === $user->id) {
            return true; // The owner of the listing
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->role === UserRole::Customer;
    }

    /**
     * Determine whether the user can cancel the model.
     */
    public function cancel(User $user, Booking $booking): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        // Only the customer can cancel their own booking (owner handles rejections differently or needs support)
        if ($booking->customer_id === $user->id) {
            return true;
        }

        return false;
    }
}
