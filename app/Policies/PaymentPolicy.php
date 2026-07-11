<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Determine whether the user can create models.
     */
    public function create(User $user, Booking $booking): bool
    {
        return $user->id === $booking->customer_id && $user->role === UserRole::Customer;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Payment $payment): bool
    {
        if ($user->role === UserRole::Admin) {
            return true;
        }

        if ($payment->booking->customer_id === $user->id) {
            return true;
        }

        return false;
    }
}
