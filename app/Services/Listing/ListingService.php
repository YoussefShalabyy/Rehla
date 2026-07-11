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
        $query = $this->buildSearchQuery($dto);

        return $query->paginate($dto->perPage, ['*'], 'page', $dto->page);
    }

    private function buildSearchQuery(SearchListingDTO $dto): \Illuminate\Database\Eloquent\Builder
    {
        $query = Listing::query()->with(['media' => fn($q) => $q->where('is_primary', true)]);

        $query->where('status', ListingStatus::Published);

        // Location Logic: Proximity, IP Geolocation, or explicit City
        if ($dto->lat !== null && $dto->lng !== null) {
            // Haversine Proximity Filter & Sorting
            $haversine = "(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude))))";
            
            // We select '*' and the computed distance
            $query->selectRaw("listings.*, {$haversine} AS distance", [$dto->lat, $dto->lng, $dto->lat])
                  ->whereRaw("{$haversine} <= ?", [$dto->lat, $dto->lng, $dto->lat, $dto->radius]);
            
            // If no explicit sort, sort by distance
            if (!$dto->sortBy) {
                $query->orderBy('distance', 'asc');
            }
        } else {
            $ipLocationApplied = false;
            if ($dto->ipAddress && !$dto->city) {
                // IP Geolocation
                if ($position = \Stevebauman\Location\Facades\Location::get($dto->ipAddress)) {
                    if ($position->cityName) {
                        $query->where('city', $position->cityName);
                        $ipLocationApplied = true;
                    }
                    if ($position->countryName) {
                        $query->where('country', $position->countryName);
                    }
                }
            }

            // If IP geolocation wasn't applied or failed, use the explicit city filter if provided
            if (!$ipLocationApplied && $dto->city) {
                $query->where('city', $dto->city);
            }
        }

        // Other filters
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
            $query->whereDoesntHave('availabilityBlocks', function ($q) use ($dto) {
                $q->where(function ($sub) use ($dto) {
                    $sub->where('start_date', '<', $dto->checkOut)
                        ->where('end_date', '>', $dto->checkIn);
                });
            });
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

        // Explicit sorting
        if ($dto->sortBy) {
            $direction = strtolower($dto->sortDirection ?? 'asc') === 'desc' ? 'desc' : 'asc';
            if (in_array($dto->sortBy, ['price', 'title', 'created_at'])) {
                $column = $dto->sortBy === 'price' ? 'base_price_cents' : $dto->sortBy;
                $query->orderBy($column, $direction);
            }
        } elseif ($dto->lat === null || $dto->lng === null) {
            // Default sort if no proximity sorting
            $query->latest();
        }

        return $query;
    }

    /**
     * @throws NotFoundException
     */
    public function findByUuid(string $uuid): Listing
    {
        $listing = Listing::with([
            'createdBy',
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

    public function create(CreateListingDTO $dto, User $admin): Listing
    {
        return DB::transaction(function () use ($dto, $admin) {
            $listing = Listing::create([
                'uuid'                   => (string) \Illuminate\Support\Str::uuid(),
                'created_by'             => $admin->id,
                'type'                   => $dto->type,
                'property_type'          => $dto->propertyType,
                'title'                  => $dto->title,
                'description'            => $dto->description,
                'address'                => $dto->address,
                'country'                => $dto->country,
                'city'                   => $dto->city,
                'latitude'               => $dto->latitude,
                'longitude'              => $dto->longitude,
                'base_price_cents'       => $dto->basePriceCents,
                'cleaning_fee_cents'     => $dto->cleaningFeeCents,
                'extra_guest_fee_cents'  => $dto->extraGuestFeeCents,
                'max_guests'             => $dto->maxGuests,
                'bedrooms'               => $dto->bedrooms,
                'bathrooms'              => $dto->bathrooms,
                'transmission'           => $dto->transmission,
                'fuel_type'              => $dto->fuelType,
                'status'                 => ListingStatus::Published, // Admin-created listings are immediately published
                'is_instant_bookable'    => true,
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
            throw new \App\Exceptions\InvalidListingStatusException('Only pending listings can be approved.', 400);
        }

        $listing->update(['status' => ListingStatus::Published]);

        return $listing;
    }

    public function reject(Listing $listing, User $admin, string $reason): Listing
    {
        if ($listing->status !== ListingStatus::Pending) {
            throw new \App\Exceptions\InvalidListingStatusException('Only pending listings can be rejected.', 400);
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

    public function getAllForAdmin(?string $status = null, int $perPage = 20): LengthAwarePaginator
    {
        $query = Listing::query()->with(['createdBy', 'media' => fn($q) => $q->where('is_primary', true)]);

        if ($status) {
            $enumStatus = ListingStatus::tryFrom($status);
            if ($enumStatus) {
                $query->where('status', $enumStatus);
            }
        }

        return $query->latest()->paginate($perPage);
    }
}
