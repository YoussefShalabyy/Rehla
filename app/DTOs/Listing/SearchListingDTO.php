<?php

declare(strict_types=1);

namespace App\DTOs\Listing;

use App\Enums\ListingType;
use App\Enums\PropertyType;
use Illuminate\Http\Request;

readonly class SearchListingDTO
{
    public function __construct(
        public ?string $city,
        public ?ListingType $type,
        public ?PropertyType $propertyType,
        public ?string $category,
        public ?string $checkIn,
        public ?string $checkOut,
        public ?int $guests,
        public ?int $minPriceCents,
        public ?int $maxPriceCents,
        public ?string $q,
        public ?string $sortBy,
        public ?string $sortDirection,
        public int $page = 1,
        public int $perPage = 20,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            city: $request->query('city'),
            type: $request->query('type') ? ListingType::tryFrom($request->query('type')) : null,
            propertyType: $request->query('property_type') ? PropertyType::tryFrom($request->query('property_type')) : null,
            category: $request->query('category'),
            checkIn: $request->query('check_in'),
            checkOut: $request->query('check_out'),
            guests: $request->query('guests') ? (int) $request->query('guests') : null,
            minPriceCents: $request->query('min_price_cents') ? (int) $request->query('min_price_cents') : null,
            maxPriceCents: $request->query('max_price_cents') ? (int) $request->query('max_price_cents') : null,
            q: $request->query('q'),
            sortBy: $request->query('sort_by'),
            sortDirection: $request->query('sort_direction'),
            page: (int) $request->query('page', 1),
            perPage: (int) $request->query('per_page', 20),
        );
    }
}
