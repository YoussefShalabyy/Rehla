<?php

declare(strict_types=1);

namespace App\Http\Resources\Listing;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingListResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Extract primary image url if loaded
        $primaryImage = null;
        if ($this->relationLoaded('media')) {
            $primaryMedia = $this->media->where('is_primary', true)->first();
            if ($primaryMedia) {
                $primaryImage = $primaryMedia->url;
            } elseif ($this->media->isNotEmpty()) {
                $primaryImage = $this->media->first()->url;
            }
        }

        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            'title' => $this->title,
            'city' => $this->city,
            'base_price_cents' => $this->base_price_cents,
            'is_instant_bookable' => $this->is_instant_bookable,
            'primary_image_url' => $primaryImage,
            'average_rating' => $this->average_rating,
            'reviews_count' => $this->reviews_count,
            'is_wishlisted' => $this->when($request->user('sanctum'), function () use ($request) {
                if ($this->relationLoaded('wishlists')) {
                    return $this->wishlists->contains('user_id', $request->user('sanctum')->id);
                }
                return $this->wishlists()->where('user_id', $request->user('sanctum')->id)->exists();
            }, false),
        ];
    }
}
