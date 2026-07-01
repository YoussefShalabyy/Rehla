<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;
use App\Enums\UserRole;

class ListingPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Listing $listing): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role === UserRole::Provider;
    }

    public function update(User $user, Listing $listing): bool
    {
        return $user->role === UserRole::Admin || $user->id === $listing->owner_id;
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $user->role === UserRole::Admin || $user->id === $listing->owner_id;
    }

    public function approve(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }

    public function reject(User $user): bool
    {
        return $user->role === UserRole::Admin;
    }
}
