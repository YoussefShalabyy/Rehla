<?php

declare(strict_types=1);

namespace App\DTOs\Listing;

use App\Http\Requests\Listing\UpdateListingRequest;

readonly class UpdateListingDTO
{
    public function __construct(
        public ?string $title,
        public ?string $description,
        public ?int $basePriceCents,
        public ?int $cleaningFeeCents,
        public ?int $extraGuestFeeCents,
        public ?int $maxGuests,
        public ?array $amenityIds,
    ) {}

    public static function fromRequest(UpdateListingRequest $request): self
    {
        return new self(
            title: $request->validated('title'),
            description: $request->validated('description'),
            basePriceCents: $request->validated('base_price_cents') ? (int) $request->validated('base_price_cents') : null,
            cleaningFeeCents: $request->validated('cleaning_fee_cents') !== null ? (int) $request->validated('cleaning_fee_cents') : null,
            extraGuestFeeCents: $request->validated('extra_guest_fee_cents') !== null ? (int) $request->validated('extra_guest_fee_cents') : null,
            maxGuests: $request->validated('max_guests') ? (int) $request->validated('max_guests') : null,
            amenityIds: $request->validated('amenity_ids'),
        );
    }
}
