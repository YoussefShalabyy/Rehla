<?php

declare(strict_types=1);

namespace App\Exceptions;

use Exception;

/**
 * Base exception for all VistaStay business exceptions.
 * All custom exceptions must extend this class.
 */
abstract class BaseException extends Exception
{
    public function __construct(
        string $message,
        private readonly int $httpStatus = 500,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $httpStatus, $previous);
    }

    public function getHttpStatus(): int
    {
        return $this->httpStatus;
    }
}
