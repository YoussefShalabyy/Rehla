<?php

declare(strict_types=1);

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;

it('registers a new customer successfully', function () {
    Event::fake([Registered::class]);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'customer',
    ]);

    $response->assertStatus(201)
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email', 'role', 'status'],
                'token'
            ]
        ]);

    expect($response->json('data.user.role'))->toBe('customer')
        ->and($response->json('data.user.password'))->toBeNull();

    $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
    Event::assertDispatched(Registered::class);
});

it('cannot register with duplicate email', function () {
    User::create([
        'uuid' => (string) str()->uuid(),
        'name' => 'Jane',
        'email' => 'duplicate@example.com',
        'password' => Hash::make('password123'),
        'role' => UserRole::Customer,
    ]);

    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'John Doe',
        'email' => 'duplicate@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'customer',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['email']);
});

it('cannot self-register as admin', function () {
    $response = $this->postJson('/api/v1/auth/register', [
        'name' => 'Admin wannabe',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin',
    ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['role']);
});

it('logs in with correct credentials', function () {
    $user = User::create([
        'uuid' => (string) str()->uuid(),
        'name' => 'Test',
        'email' => 'login@example.com',
        'password' => Hash::make('password123'),
        'role' => UserRole::Customer,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'identifier' => 'login@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['user', 'token']]);

    $user->refresh();
    expect($user->last_login_at)->not->toBeNull();
});

it('logs in with phone number', function () {
    $user = User::create([
        'uuid' => (string) str()->uuid(),
        'name' => 'Phone User',
        'email' => 'phone@example.com',
        'phone' => '+201000000000',
        'password' => Hash::make('password123'),
        'role' => UserRole::Customer,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'identifier' => '+201000000000',
        'password' => 'password123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonStructure(['data' => ['user', 'token']]);
});

it('returns 401 with wrong password', function () {
    User::create([
        'uuid' => (string) str()->uuid(),
        'name' => 'Test',
        'email' => 'wrong@example.com',
        'password' => Hash::make('password123'),
        'role' => UserRole::Customer,
    ]);

    $response = $this->postJson('/api/v1/auth/login', [
        'identifier' => 'wrong@example.com',
        'password' => 'wrongpassword',
    ]);

    $response->assertStatus(401); // UnauthorizedActionException is mapped to 401 ?
});

it('returns current user on GET /me', function () {
    $user = User::create([
        'uuid' => (string) str()->uuid(),
        'name' => 'Test ME',
        'email' => 'me@example.com',
        'password' => Hash::make('password123'),
        'role' => UserRole::Customer,
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    $response = $this->withToken($token)->getJson('/api/v1/auth/me');

    $response->assertStatus(200)
        ->assertJsonPath('data.name', 'Test ME');
});

it('returns 401 on GET /me without token', function () {
    $response = $this->getJson('/api/v1/auth/me');
    $response->assertStatus(401);
});

it('logout revokes token', function () {
    $user = User::create([
        'uuid' => (string) str()->uuid(),
        'name' => 'Logout Test',
        'email' => 'logout@example.com',
        'password' => Hash::make('password123'),
        'role' => UserRole::Customer,
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    // Logout
    $response = $this->withToken($token)->postJson('/api/v1/auth/logout');
    $response->assertStatus(200);

    // Clear cached auth state
    auth('sanctum')->forgetUser();
    
    // Subsequent request should fail
    $subsequentResponse = $this->withToken($token)->getJson('/api/v1/auth/me');
    $subsequentResponse->assertStatus(401);
});

it('social auth creates user if not exists', function () {
    $response = $this->postJson('/api/v1/auth/google', [
        'id_token' => 'mock_token',
        'email' => 'social@example.com',
        'name' => 'Social User',
        'provider_id' => 'google_123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('users', [
        'email' => 'social@example.com',
        'provider' => 'google',
        'provider_id' => 'google_123',
    ]);
});

it('social auth returns existing user', function () {
    User::create([
        'uuid' => (string) str()->uuid(),
        'name' => 'Existing Social',
        'email' => 'existing@example.com',
        'password' => Hash::make('password123'),
        'role' => UserRole::Customer,
        'provider' => 'google',
        'provider_id' => 'google_123',
    ]);

    $response = $this->postJson('/api/v1/auth/google', [
        'id_token' => 'mock_token',
        'email' => 'existing@example.com',
        'name' => 'Existing Social',
        'provider_id' => 'google_123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    expect(User::where('email', 'existing@example.com')->count())->toBe(1);
});

it('social auth creates user if not exists for apple', function () {
    $response = $this->postJson('/api/v1/auth/apple', [
        'identity_token' => 'mock_token',
        'email' => 'apple@example.com',
        'name' => 'Apple User',
        'provider_id' => 'apple_123',
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('users', [
        'email' => 'apple@example.com',
        'provider' => 'apple',
        'provider_id' => 'apple_123',
    ]);
});

it('can delete account', function () {
    $user = User::create([
        'uuid' => (string) str()->uuid(),
        'name' => 'To Be Deleted',
        'email' => 'delete@example.com',
        'password' => Hash::make('password123'),
        'role' => UserRole::Customer,
    ]);

    $token = $user->createToken('auth_token')->plainTextToken;

    $response = $this->withToken($token)->deleteJson('/api/v1/auth/delete');

    $response->assertStatus(200)
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Account deleted successfully.');

    $this->assertSoftDeleted('users', [
        'id' => $user->id,
        'email' => 'delete@example.com',
    ]);
});

it('returns 401 when deleting account unauthenticated', function () {
    $response = $this->deleteJson('/api/v1/auth/delete');
    $response->assertStatus(401);
});
