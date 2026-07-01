<?php

declare(strict_types=1);

namespace App\DTOs\Listing;

use App\Enums\ListingType;
use App\Enums\PropertyType;
use App\Http\Requests\Listing\CreateListingRequest;

readonly class CreateListingDTO
{
    public function __construct(
        public ListingType $type,
        public ?PropertyType $propertyType,
        public string $title,
        public string $description,
        public string $address,
        public string $country,
        public string $city,
        public float $latitude,
        public float $longitude,
        public int $basePriceCents,
        public int $cleaningFeeCents,
        public int $extraGuestFeeCents,
        public int $maxGuests,
        public ?int $bedrooms,
        public ?float $bathrooms,
        public ?string $transmission,
        public ?string $fuelType,
        public array $amenityIds,
    ) {}

    public static function fromRequest(CreateListingRequest $request): self
    {
        return new self(
            type: ListingType::from($request->validated('type')),
            propertyType: $request->validated('property_type') ? PropertyType::from($request->validated('property_type')) : null,
            title: $request->validated('title'),
            description: $request->validated('description'),
            address: $request->validated('address'),
            country: $request->validated('country'),
            city: $request->validated('city'),
            latitude: (float) $request->validated('latitude'),
            longitude: (float) $request->validated('longitude'),
            basePriceCents: (int) $request->validated('base_price_cents'),
            cleaningFeeCents: (int) $request->validated('cleaning_fee_cents', 0),
            extraGuestFeeCents: (int) $request->validated('extra_guest_fee_cents', 0),
            maxGuests: (int) $request->validated('max_guests'),
            bedrooms: $request->validated('bedrooms') ? (int) $request->validated('bedrooms') : null,
            bathrooms: $request->validated('bathrooms') ? (float) $request->validated('bathrooms') : null,
            transmission: $request->validated('transmission'),
            fuelType: $request->validated('fuel_type'),
            amenityIds: $request->validated('amenity_ids', []),
        );
    }
}
