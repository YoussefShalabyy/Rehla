<?php

declare(strict_types=1);

namespace App\DTOs\Review;

use App\Http\Requests\Review\CreateReviewRequest;

readonly class CreateReviewDTO
{
    public function __construct(
        public string $bookingUuid,
        public int $rating,
        public ?string $comment,
    ) {}

    public static function fromRequest(CreateReviewRequest $request): self
    {
        return new self(
            bookingUuid: $request->input('booking_uuid'),
            rating: (int) $request->input('rating'),
            comment: $request->input('comment'),
        );
    }
}
