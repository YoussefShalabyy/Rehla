<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\UserAdminResource;
use App\Models\User;
use App\Services\Admin\AdminDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private AdminDashboardService $service) {}

    public function index(Request $request): JsonResponse
    {
        $query = User::withCount(['listings', 'bookings']);

        if ($request->has('role')) {
            $query->where('role', $request->query('role'));
        }

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $users = $query->paginate(20);

        return response()->json([
            'success' => true,
            'message' => 'Users retrieved successfully.',
            'data'    => UserAdminResource::collection($users)->response()->getData(true)['data'],
            'meta'    => UserAdminResource::collection($users)->response()->getData(true)['meta'],
            'errors'  => null,
        ]);
    }

    public function updateStatus(Request $request, string $uuid): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|string|in:active,suspended',
        ]);

        $user = User::where('uuid', $uuid)->firstOrFail();

        $user = $this->service->updateUserStatus($user, $validated['status']);

        return response()->json([
            'success' => true,
            'message' => 'User status updated successfully.',
            'data'    => new UserAdminResource($user->loadCount(['listings', 'bookings'])),
            'meta'    => null,
            'errors'  => null,
        ]);
    }
}
