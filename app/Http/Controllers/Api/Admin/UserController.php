<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserAdminResource;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Admin\AdminDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function __construct(private AdminDashboardService $service)
    {
    }

    /**
     * List all users with filters.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::withCount(['bookings']);

        if ($request->filled('role')) {
            $query->where('role', $request->query('role'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->query('status'));
        }

        $users = $query->latest()->paginate(20);

        return $this->paginated($users, UserAdminResource::class);
    }

    /**
     * Show a single user's detail including booking history.
     */
    public function show(string $uuid): JsonResponse
    {
        $user = User::where('uuid', $uuid)
            ->withCount(['bookings'])
            ->with(['bookings' => fn($q) => $q->latest()->take(10)->with('listing:id,uuid,title')])
            ->firstOrFail();

        return $this->success(new UserAdminResource($user));
    }

    /**
     * Create a new admin user (only accessible by existing admins).
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'     => ['required', 'string', 'max:255'],
            'email'    => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'phone'    => ['nullable', 'string', 'max:20'],
        ]);

        $user = User::create([
            'uuid'     => (string) Str::uuid(),
            'name'     => $validated['name'],
            'email'    => $validated['email'],
            'phone'    => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role'     => UserRole::Admin,
            'status'   => UserStatus::Active,
        ]);

        // Create wallet for admin
        Wallet::create([
            'uuid'          => (string) Str::uuid(),
            'user_id'       => $user->id,
            'balance_cents' => 0,
        ]);

        return $this->created(new UserAdminResource($user), 'Admin user created successfully.');
    }

    /**
     * Update user status (active / suspended).
     */
    public function updateStatus(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:active,suspended'],
        ]);

        $user = User::where('uuid', $uuid)->firstOrFail();
        $user = $this->service->updateUserStatus($user, $validated['status']);

        return $this->success(new UserAdminResource($user), 'User status updated successfully.');
    }

    /**
     * Hard delete a user (admin only — use with care).
     */
    public function destroy(string $uuid): JsonResponse
    {
        $user = User::where('uuid', $uuid)->firstOrFail();

        // Revoke all tokens first
        $user->tokens()->delete();
        $user->forceDelete();

        return $this->success(null, 'User permanently deleted.');
    }
}
