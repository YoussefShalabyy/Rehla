<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a listing is not available for the requested action
 * (e.g., not published, not instant-bookable).
 * Maps to HTTP 422 Unprocessable Entity.
 */
final class ListingNotAvailableException extends BaseException
{
    public function __construct(string $message = 'This listing is not available for booking.')
    {
        parent::__construct($message, 422);
    }
}
