<?php

declare(strict_types=1);

namespace App\Exceptions;

/**
 * Thrown when an operation requires a resource that does not exist.
 * Maps to HTTP 404 Not Found.
 */
final class NotFoundException extends BaseException
{
    public function __construct(string $resource = 'Resource', string $message = '')
    {
        parent::__construct(
            $message ?: "{$resource} not found.",
            404,
        );
    }
}
