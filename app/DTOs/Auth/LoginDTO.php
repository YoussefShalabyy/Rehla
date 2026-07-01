<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

use App\Http\Requests\Auth\LoginRequest;

readonly class LoginDTO
{
    public function __construct(
        public string $identifier,
        public string $password,
    ) {}

    public static function fromRequest(LoginRequest $request): self
    {
        return new self(
            identifier: $request->validated('identifier'),
            password: $request->validated('password'),
        );
    }
}
