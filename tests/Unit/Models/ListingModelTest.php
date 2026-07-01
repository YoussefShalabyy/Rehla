<?php

declare(strict_types=1);

use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('scopes work correctly', function () {
    $owner = User::create(['uuid' => (string) str()->uuid(), 'name' => 'owner', 'email' => 'owner@ex.com', 'password' => '123', 'role' => \App\Enums\UserRole::Provider, 'status' => \App\Enums\UserStatus::Active]);
    
    Listing::create([
        'uuid' => (string) str()->uuid(),
        'owner_id' => $owner->id,
        'status' => ListingStatus::Published,
        'type' => ListingType::Property,
        'city' => 'Cairo',
        'title' => 'Test 1',
        'description' => 'Test',
        'address' => 'Test',
        'country' => 'EG',
        'latitude' => 30.0,
        'longitude' => 31.0,
        'base_price_cents' => 1000,
        'max_guests' => 2,
    ]);

    Listing::create([
        'uuid' => (string) str()->uuid(),
        'owner_id' => $owner->id,
        'status' => ListingStatus::Pending,
        'type' => ListingType::Car,
        'city' => 'Alexandria',
        'title' => 'Test 2',
        'description' => 'Test',
        'address' => 'Test',
        'country' => 'EG',
        'latitude' => 30.0,
        'longitude' => 31.0,
        'base_price_cents' => 1000,
        'max_guests' => 2,
    ]);

    expect(Listing::published()->count())->toBe(1)
        ->and(Listing::ofType(ListingType::Car)->count())->toBe(1)
        ->and(Listing::inCity('Cairo')->count())->toBe(1);
});
