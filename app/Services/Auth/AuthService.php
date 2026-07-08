<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Exceptions\UnauthorizedActionException;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthService
{
    /**
     * @return array{user: User, token: string}
     */
    public function register(RegisterDTO $dto): array
    {
        $user = User::create([
            'uuid' => (string) Str::uuid(),
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone ?? null,
            'password' => Hash::make($dto->password),
            'role' => $dto->role,
        ]);

        event(new Registered($user));

        \App\Models\Wallet::create([
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'user_id' => $user->id,
            'balance_cents' => 0,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    /**
     * @return array{user: User, token: string}
     * @throws \Illuminate\Auth\AuthenticationException
     */
    public function login(LoginDTO $dto): array
    {
        $user = User::where('email', $dto->identifier)
                    ->orWhere('phone', $dto->identifier)
                    ->first();

        if (! $user || ! Hash::check($dto->password, $user->password)) {
            throw new \Illuminate\Auth\AuthenticationException('Invalid credentials.');
        }

        $user->update(['last_login_at' => now()]);

        // Revoke existing tokens for a cleaner state, or allow multiple.
        // For MVP, we'll just issue a new one.
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        // currentAccessToken() might be null in some test scenarios, so we revoke all tokens or the specific one.
        // To ensure the token is revoked, we can just delete all tokens for the user in this MVP.
        $user->tokens()->delete();
    }

    public function findOrCreateSocialUser(string $provider, string $providerId, string $email, string $name): User
    {
        $user = User::where('provider_id', $providerId)
            ->orWhere('email', $email)
            ->first();

        if (! $user) {
            $user = User::create([
                'uuid' => (string) Str::uuid(),
                'name' => $name,
                'email' => $email,
                'password' => Hash::make(Str::random(24)),
                'role' => \App\Enums\UserRole::Customer,
                'provider' => $provider,
                'provider_id' => $providerId,
            ]);
            
            event(new Registered($user));

            \App\Models\Wallet::create([
                'uuid' => (string) \Illuminate\Support\Str::uuid(),
                'user_id' => $user->id,
                'balance_cents' => 0,
            ]);
        } else {
            // Update provider info if logging in with social for the first time on existing email
            if (! $user->provider_id) {
                $user->update([
                    'provider' => $provider,
                    'provider_id' => $providerId,
                ]);
            }
            $user->update(['last_login_at' => now()]);
        }

        return $user;
    }

    public function deleteAccount(User $user): void
    {
        // Revoke all tokens
        $user->tokens()->delete();

        // Soft delete all user's listings (if they are a provider)
        foreach ($user->listings as $listing) {
            $listing->delete();
        }

        // Soft delete the user
        $user->delete();
    }
}
