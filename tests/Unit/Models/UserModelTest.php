<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('casts role and status correctly', function () {
    $user = User::create([
        'uuid' => (string) str()->uuid(),
        'role' => UserRole::Provider,
        'status' => UserStatus::Active,
        'email' => 'test@example.com',
        'password' => bcrypt('password'),
        'name' => 'Test',
    ]);

    expect($user->role)->toBe(UserRole::Provider)
        ->and($user->status)->toBe(UserStatus::Active);
});

it('has correct relationships defined', function () {
    $user = new User();

    expect($user->listings())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class)
        ->and($user->bookings())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class)
        ->and($user->reviews())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});
