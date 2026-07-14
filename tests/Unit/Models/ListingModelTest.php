<?php

declare(strict_types=1);

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('scopes work correctly', function () {
    $admin = User::create([
        'uuid'     => (string) str()->uuid(),
        'name'     => 'Admin',
        'email'    => 'admin@ex.com',
        'password' => '123',
        'role'     => UserRole::Admin,
        'status'   => UserStatus::Active,
    ]);

    Listing::create([
        'uuid'             => (string) str()->uuid(),
        'created_by'       => $admin->id,
        'status'           => ListingStatus::Active,
        'type'             => ListingType::Property,
        'city'             => 'Cairo',
        'title'            => 'Test 1',
        'description'      => 'Test',
        'address'          => 'Test',
        'country'          => 'EG',
        'latitude'         => 30.0,
        'longitude'        => 31.0,
        'base_price_cents' => 1000,
        'max_guests'       => 2,
    ]);

    Listing::create([
        'uuid'             => (string) str()->uuid(),
        'created_by'       => $admin->id,
        'status'           => ListingStatus::Hidden,
        'type'             => ListingType::Car,
        'city'             => 'Alexandria',
        'title'            => 'Test 2',
        'description'      => 'Test',
        'address'          => 'Test',
        'country'          => 'EG',
        'latitude'         => 30.0,
        'longitude'        => 31.0,
        'base_price_cents' => 1000,
        'max_guests'       => 2,
    ]);

    expect(Listing::active()->count())->toBe(1)
        ->and(Listing::ofType(ListingType::Car)->count())->toBe(1)
        ->and(Listing::inCity('Cairo')->count())->toBe(1);
});
