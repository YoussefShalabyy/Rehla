<?php

declare(strict_types=1);

namespace App\DTOs\Auth;

use App\Enums\UserRole;
use App\Http\Requests\Auth\RegisterRequest;

readonly class RegisterDTO
{
    public function __construct(
        public string $name,
        public string $email,
        public ?string $phone,
        public string $password,
        public UserRole $role,
    ) {}

    public static function fromRequest(RegisterRequest $request): self
    {
        return new self(
            name: $request->validated('name'),
            email: $request->validated('email'),
            phone: $request->validated('phone'),
            password: $request->validated('password'),
            role: UserRole::from($request->validated('role')),
        );
    }
}
