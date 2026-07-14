<?php

declare(strict_types=1);

use App\Enums\ListingStatus;
use App\Enums\UserRole;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->admin = User::create([
        'uuid'     => (string) Str::uuid(),
        'name'     => 'Admin',
        'email'    => 'admin@example.com',
        'password' => Hash::make('password'),
        'role'     => UserRole::Admin,
    ]);

    $this->customer = User::create([
        'uuid'     => (string) Str::uuid(),
        'name'     => 'Customer',
        'email'    => 'customer@example.com',
        'password' => Hash::make('password'),
        'role'     => UserRole::Customer,
    ]);
});

it('admin can create a listing with status published', function () {
    $response = $this->actingAs($this->admin)->postJson('/api/v1/admin/listings', [
        'type'             => 'property',
        'property_type'    => 'apartment',
        'title'            => 'Nice Apartment',
        'description'      => 'A very nice apartment.',
        'address'          => '123 Main St',
        'country'          => 'Egypt',
        'city'             => 'Cairo',
        'latitude'         => 30.0444,
        'longitude'        => 31.2357,
        'base_price_cents' => 50000,
        'max_guests'       => 4,
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('listings', [
        'title'      => 'Nice Apartment',
        'status'     => 'active',
        'created_by' => $this->admin->id,
    ]);
});

it('customer cannot create a listing', function () {
    $response = $this->actingAs($this->customer)->postJson('/api/v1/admin/listings', [
        'type'  => 'property',
        'title' => 'Nice Apartment',
    ]);

    $response->assertStatus(403);
});

it('unauthenticated user cannot create a listing', function () {
    $response = $this->postJson('/api/v1/admin/listings', [
        'type'  => 'property',
        'title' => 'Nice Apartment',
    ]);

    $response->assertStatus(401);
});

it('admin updates listing status', function () {
    $listing = Listing::factory()->create([
        'created_by' => $this->admin->id,
        'status'     => ListingStatus::Hidden,
    ]);

    $response = $this->actingAs($this->admin)->putJson("/api/v1/admin/listings/{$listing->uuid}/status", [
        'status' => 'active',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'active');

    $this->assertDatabaseHas('listings', [
        'id'     => $listing->id,
        'status' => 'active',
    ]);
});

it('admin can hide a listing', function () {
    $listing = Listing::factory()->create([
        'created_by' => $this->admin->id,
        'status'     => ListingStatus::Active,
    ]);

    $response = $this->actingAs($this->admin)->putJson("/api/v1/admin/listings/{$listing->uuid}/status", [
        'status' => 'hidden',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.status', 'hidden');
});

it('active listings appear in public search', function () {
    Listing::factory()->create([
        'created_by' => $this->admin->id,
        'status'     => ListingStatus::Active,
        'title'      => 'Active Listing',
    ]);

    $response = $this->getJson('/api/v1/listings');

    $response->assertStatus(200)
        ->assertJsonFragment(['title' => 'Active Listing']);
});

it('hidden listings do not appear in public search', function () {
    Listing::factory()->create([
        'created_by' => $this->admin->id,
        'status'     => ListingStatus::Hidden,
        'title'      => 'Hidden Listing',
    ]);

    $response = $this->getJson('/api/v1/listings');

    $response->assertStatus(200)
        ->assertJsonMissing(['title' => 'Hidden Listing']);
});

it('admin can update a listing', function () {
    $listing = Listing::factory()->create([
        'created_by' => $this->admin->id,
        'title'      => 'Old Title',
    ]);

    $response = $this->actingAs($this->admin)->putJson("/api/v1/admin/listings/{$listing->uuid}", [
        'title' => 'New Title',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.title', 'New Title');
});

it('customer cannot update a listing', function () {
    $listing = Listing::factory()->create([
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->customer)->putJson("/api/v1/admin/listings/{$listing->uuid}", [
        'title' => 'Hacked Title',
    ]);

    $response->assertStatus(403);
});

it('admin can soft-delete a listing', function () {
    $listing = Listing::factory()->create([
        'created_by' => $this->admin->id,
    ]);

    $response = $this->actingAs($this->admin)->deleteJson("/api/v1/admin/listings/{$listing->uuid}");

    $response->assertStatus(200);
    $this->assertSoftDeleted('listings', ['id' => $listing->id]);
});

it('unauthenticated user can view active listing detail', function () {
    $listing = Listing::factory()->create([
        'created_by' => $this->admin->id,
        'status'     => ListingStatus::Active,
        'title'      => 'Public View',
    ]);

    $response = $this->getJson("/api/v1/listings/{$listing->uuid}");

    $response->assertStatus(200)
        ->assertJsonPath('data.title', 'Public View');
});

it('search filters by city correctly', function () {
    Listing::factory()->create([
        'created_by' => $this->admin->id,
        'status'     => ListingStatus::Active,
        'city'       => 'Alexandria',
    ]);

    Listing::factory()->create([
        'created_by' => $this->admin->id,
        'status'     => ListingStatus::Active,
        'city'       => 'Cairo',
    ]);

    $response = $this->getJson('/api/v1/listings?city=Cairo');

    $response->assertStatus(200);
    expect(count($response->json('data')))->toBe(1)
        ->and($response->json('data.0.city'))->toBe('Cairo');
});

it('search paginates results', function () {
    Listing::factory()->count(25)->create([
        'created_by' => $this->admin->id,
        'status'     => ListingStatus::Active,
    ]);

    $response = $this->getJson('/api/v1/listings?per_page=10');

    $response->assertStatus(200)
        ->assertJsonStructure([
            'meta' => [
                'pagination' => ['total', 'count', 'per_page', 'current_page', 'total_pages'],
            ],
        ]);

    expect(count($response->json('data')))->toBe(10)
        ->and($response->json('meta.pagination.total'))->toBeGreaterThanOrEqual(25);
});
