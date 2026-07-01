<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a payment attempt fails.
 * Maps to HTTP 422 Unprocessable Entity.
 */
final class PaymentFailedException extends BaseException
{
    public function __construct(string $message = 'Payment processing failed. Please try again.')
    {
        parent::__construct($message, 422);
    }
}
