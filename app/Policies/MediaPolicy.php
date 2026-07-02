<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\Media;
use App\Models\User;

class MediaPolicy
{
    /**
     * Determine whether the user can upload media for the listing.
     */
    public function upload(User $user, Listing $listing): bool
    {
        return $user->id === $listing->owner_id;
    }

    /**
     * Determine whether the user can delete the media.
     */
    public function delete(User $user, Media $media): bool
    {
        if ($media->entity_type === 'listing') {
            $listing = Listing::find($media->entity_id);
            return $listing && $listing->owner_id === $user->id;
        }

        if ($media->entity_type === 'user') {
            return $media->entity_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can set media as primary.
     */
    public function setPrimary(User $user, Media $media): bool
    {
        return $this->delete($user, $media); // Same rules as delete
    }

    /**
     * Determine whether the user can reorder media for a listing.
     */
    public function reorder(User $user, Listing $listing): bool
    {
        return $this->upload($user, $listing); // Same rules as upload
    }
}
