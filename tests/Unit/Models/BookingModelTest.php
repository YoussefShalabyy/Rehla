<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('auto-generates uuid and booking_reference on create', function () {
    $customer = User::create(['uuid' => (string) str()->uuid(), 'name' => 'test', 'email' => 'test@ex.com', 'password' => '123', 'role' => UserRole::Customer, 'status' => UserStatus::Active]);
    $admin    = User::create(['uuid' => (string) str()->uuid(), 'name' => 'admin', 'email' => 'admin@ex.com', 'password' => '123', 'role' => UserRole::Admin, 'status' => UserStatus::Active]);
    $listing  = Listing::create([
        'uuid'             => (string) str()->uuid(),
        'created_by'       => $admin->id,
        'type'             => \App\Enums\ListingType::Property,
        'title'            => 'Test Listing',
        'description'      => 'Test',
        'address'          => 'Test',
        'country'          => 'EG',
        'city'             => 'Cairo',
        'latitude'         => 30.0,
        'longitude'        => 31.0,
        'base_price_cents' => 1000,
        'max_guests'       => 2,
    ]);

    $booking = Booking::create([
        'listing_id'         => $listing->id,
        'customer_id'        => $customer->id,
        'check_in_date'      => now()->addDays(1),
        'check_out_date'     => now()->addDays(3),
        'guests_count'       => 1,
        'total_amount_cents' => 2000,
        'platform_fee_cents' => 200,
    ]);

    expect($booking->uuid)->not->toBeNull()
        ->and($booking->booking_reference)->toStartWith('VS-')
        ->and(strlen($booking->booking_reference))->toBeGreaterThan(5);
});

it('has correct relationships defined', function () {
    $booking = new Booking();

    expect($booking->listing())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)
        ->and($booking->customer())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)
        ->and($booking->payment())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class)
        ->and($booking->review())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasOne::class);
});
