<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->authService = new AuthService();
    }

    public function test_delete_account_soft_deletes_user_and_revokes_tokens()
    {
        $user = User::create([
            'uuid' => (string) str()->uuid(),
            'name' => 'To Be Deleted',
            'email' => 'delete@example.com',
            'password' => Hash::make('password123'),
            'role' => UserRole::Customer,
        ]);

        $user->createToken('test_token');

        $this->assertDatabaseCount('personal_access_tokens', 1);

        $this->authService->deleteAccount($user);

        // Assert user is soft deleted
        $this->assertSoftDeleted('users', [
            'id' => $user->id,
        ]);

        // Assert tokens are deleted
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
