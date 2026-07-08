<?php

declare(strict_types=1);

namespace App\Services\Listing;

use App\DTOs\Listing\CreateListingDTO;
use App\DTOs\Listing\SearchListingDTO;
use App\DTOs\Listing\UpdateListingDTO;
use App\Enums\ListingStatus;
use App\Exceptions\NotFoundException;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ListingService
{
    public function search(SearchListingDTO $dto): LengthAwarePaginator
    {
        $query = Listing::query()->with(['media' => fn($q) => $q->where('is_primary', true)]);

        $query->where('status', ListingStatus::Published);

        if ($dto->city) {
            $query->where('city', $dto->city);
        }

        if ($dto->type) {
            $query->where('type', $dto->type);
        }

        if ($dto->propertyType) {
            $query->where('property_type', $dto->propertyType);
        }

        if ($dto->category) {
            $query->where('category', $dto->category);
        }

        if ($dto->guests) {
            $query->where('max_guests', '>=', $dto->guests);
        }

        if ($dto->minPriceCents !== null) {
            $query->where('base_price_cents', '>=', $dto->minPriceCents);
        }

        if ($dto->maxPriceCents !== null) {
            $query->where('base_price_cents', '<=', $dto->maxPriceCents);
        }

        if ($dto->checkIn && $dto->checkOut) {
            // Check availability - listings that do not have availability blocks intersecting with dates
            $query->whereDoesntHave('availabilityBlocks', function ($q) use ($dto) {
                $q->where(function ($sub) use ($dto) {
                    $sub->where('start_date', '<', $dto->checkOut)
                        ->where('end_date', '>', $dto->checkIn);
                });
            });
            // Also, need to check existing confirmed/active bookings intersection, assuming they block dates
            $query->whereDoesntHave('bookings', function ($q) use ($dto) {
                $q->whereIn('status', [\App\Enums\BookingStatus::Confirmed, \App\Enums\BookingStatus::Active])
                  ->where(function ($sub) use ($dto) {
                      $sub->where('check_in_date', '<', $dto->checkOut)
                          ->where('check_out_date', '>', $dto->checkIn);
                  });
            });
        }

        if ($dto->q) {
            $query->where(function ($q) use ($dto) {
                $q->where('title', 'like', "%{$dto->q}%")
                  ->orWhere('city', 'like', "%{$dto->q}%")
                  ->orWhere('country', 'like', "%{$dto->q}%");
            });
        }

        if ($dto->sortBy) {
            $direction = strtolower($dto->sortDirection ?? 'asc') === 'desc' ? 'desc' : 'asc';
            if (in_array($dto->sortBy, ['price', 'title', 'created_at'])) {
                $column = $dto->sortBy === 'price' ? 'base_price_cents' : $dto->sortBy;
                $query->orderBy($column, $direction);
            }
        } else {
            // Default sort
            $query->latest();
        }

        return $query->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }

    /**
     * @throws NotFoundException
     */
    public function findByUuid(string $uuid): Listing
    {
        $listing = Listing::with([
            'owner', 
            'amenities', 
            'media', 
            'reviews' => function($q) {
                $q->where('status', \App\Enums\ReviewStatus::Approved)
                  ->latest()
                  ->take(3)
                  ->with('reviewer');
            }
        ])->where('uuid', $uuid)->first();

        if (! $listing) {
            throw new NotFoundException('Listing not found.');
        }

        return $listing;
    }

    public function create(CreateListingDTO $dto, User $owner): Listing
    {
        return DB::transaction(function () use ($dto, $owner) {
            $listing = Listing::create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'owner_id' => $owner->id,
                'type' => $dto->type,
                'property_type' => $dto->propertyType,
                'title' => $dto->title,
                'description' => $dto->description,
                'address' => $dto->address,
                'country' => $dto->country,
                'city' => $dto->city,
                'latitude' => $dto->latitude,
                'longitude' => $dto->longitude,
                'base_price_cents' => $dto->basePriceCents,
                'cleaning_fee_cents' => $dto->cleaningFeeCents,
                'extra_guest_fee_cents' => $dto->extraGuestFeeCents,
                'max_guests' => $dto->maxGuests,
                'bedrooms' => $dto->bedrooms,
                'bathrooms' => $dto->bathrooms,
                'transmission' => $dto->transmission,
                'fuel_type' => $dto->fuelType,
                'status' => ListingStatus::Pending,
                'is_instant_bookable' => false,
            ]);

            if (!empty($dto->amenityIds)) {
                $listing->amenities()->sync($dto->amenityIds);
            }

            return $listing->load(['amenities']);
        });
    }

    public function update(Listing $listing, UpdateListingDTO $dto): Listing
    {
        $updateData = [];

        if ($dto->title !== null) {
            $updateData['title'] = $dto->title;
        }
        if ($dto->description !== null) {
            $updateData['description'] = $dto->description;
        }
        if ($dto->basePriceCents !== null) {
            $updateData['base_price_cents'] = $dto->basePriceCents;
        }
        if ($dto->cleaningFeeCents !== null) {
            $updateData['cleaning_fee_cents'] = $dto->cleaningFeeCents;
        }
        if ($dto->extraGuestFeeCents !== null) {
            $updateData['extra_guest_fee_cents'] = $dto->extraGuestFeeCents;
        }
        if ($dto->maxGuests !== null) {
            $updateData['max_guests'] = $dto->maxGuests;
        }

        return DB::transaction(function () use ($listing, $updateData, $dto) {
            if (!empty($updateData)) {
                $listing->update($updateData);
            }

            if ($dto->amenityIds !== null) {
                $listing->amenities()->sync($dto->amenityIds);
            }

            return $listing->fresh(['amenities', 'media']);
        });
    }

    public function approve(Listing $listing, User $admin): Listing
    {
        if ($listing->status !== ListingStatus::Pending) {
            throw new \App\Exceptions\BaseException('Only pending listings can be approved.', 400);
        }

        $listing->update(['status' => ListingStatus::Published]);

        return $listing;
    }

    public function reject(Listing $listing, User $admin, string $reason): Listing
    {
        if ($listing->status !== ListingStatus::Pending) {
            throw new \App\Exceptions\BaseException('Only pending listings can be rejected.', 400);
        }

        $listing->update(['status' => ListingStatus::Rejected]);

        // Here we could optionally store the $reason in a listing_rejections table or similar
        // For MVP, just updating status is sufficient.

        return $listing;
    }

    public function delete(Listing $listing): void
    {
        $listing->delete();
    }

    public function getOwnerListings(User $owner, int $perPage = 20): LengthAwarePaginator
    {
        return Listing::with(['media' => fn($q) => $q->where('is_primary', true)])
            ->where('owner_id', $owner->id)
            ->latest()
            ->paginate($perPage);
    }

    public function getAllForAdmin(?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Listing::query()->with(['owner', 'media' => fn($q) => $q->where('is_primary', true)]);

        if ($status) {
            $enumStatus = ListingStatus::tryFrom($status);
            if ($enumStatus) {
                $query->where('status', $enumStatus);
            }
        }

        return $query->latest()->paginate($perPage);
    }
}
