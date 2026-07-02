<?php

declare(strict_types=1);

namespace App\DTOs\Booking;

use App\Http\Requests\Booking\CreateBookingRequest;

readonly class CreateBookingDTO
{
    public function __construct(
        public string $listingUuid,
        public string $checkInDate,
        public string $checkOutDate,
        public int $guestsCount,
        public ?string $notes,
    ) {
    }

    public static function fromRequest(CreateBookingRequest $request): self
    {
        return new self(
            listingUuid: $request->validated('listing_uuid'),
            checkInDate: $request->validated('check_in_date'),
            checkOutDate: $request->validated('check_out_date'),
            guestsCount: (int) $request->validated('guests_count'),
            notes: $request->validated('notes'),
        );
    }
}
