<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when a user attempts an action they are not authorized to perform.
 * Maps to HTTP 403 Forbidden.
 */
final class UnauthorizedActionException extends BaseException
{
    public function __construct(string $message = 'You are not authorized to perform this action.')
    {
        parent::__construct($message, 403);
    }
}
