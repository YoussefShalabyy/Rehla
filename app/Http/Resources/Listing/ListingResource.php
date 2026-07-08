<?php

declare(strict_types=1);

namespace App\Http\Resources\Listing;

use App\Http\Resources\Auth\AuthUserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ListingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type,
            'property_type' => $this->property_type,
            'title' => $this->title,
            'description' => $this->description,
            'address' => $this->address,
            'city' => $this->city,
            'country' => $this->country,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'base_price_cents' => $this->base_price_cents,
            'cleaning_fee_cents' => $this->cleaning_fee_cents,
            'extra_guest_fee_cents' => $this->extra_guest_fee_cents,
            'max_guests' => $this->max_guests,
            'bedrooms' => $this->bedrooms,
            'bathrooms' => $this->bathrooms,
            'transmission' => $this->transmission,
            'fuel_type' => $this->fuel_type,
            'status' => $this->status,
            'is_instant_bookable' => $this->is_instant_bookable,
            
            // Relationships
            'owner' => new AuthUserResource($this->whenLoaded('owner')),
            'amenities' => $this->whenLoaded('amenities', function () {
                return $this->amenities->map(function ($amenity) {
                    return [
                        'id' => $amenity->id,
                        'name' => $amenity->name,
                        'icon' => $amenity->icon,
                    ];
                });
            }),
            'media' => $this->whenLoaded('media', function () {
                return $this->media->map(function ($m) {
                    return [
                        'url' => $m->url,
                        'type' => $m->type,
                        'is_primary' => $m->is_primary,
                        'order' => $m->order,
                    ];
                });
            }),
            
            // Computed fields (mocked for now, will be implemented with Reviews phase)
            'average_rating' => $this->average_rating,
            'reviews_count' => $this->reviews_count,
            'latest_reviews' => $this->whenLoaded('reviews', function () {
                return \App\Http\Resources\Review\ReviewResource::collection($this->reviews);
            }),
            'is_wishlisted' => $this->when($request->user('sanctum'), function () use ($request) {
                if ($this->relationLoaded('wishlists')) {
                    return $this->wishlists->contains('user_id', $request->user('sanctum')->id);
                }
                return $this->wishlists()->where('user_id', $request->user('sanctum')->id)->exists();
            }, false),
        ];
    }
}
