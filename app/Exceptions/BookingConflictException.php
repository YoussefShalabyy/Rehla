<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a booking attempt fails due to date conflicts.
 * Maps to HTTP 409 Conflict.
 */
final class BookingConflictException extends BaseException
{
    public function __construct(string $message = 'The selected dates are not available for this listing.')
    {
        parent::__construct($message, 409);
    }
}
