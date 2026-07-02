<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\DashboardStatsResource;
use App\Services\Admin\AdminDashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __construct(private AdminDashboardService $service) {}

    public function stats(): JsonResponse
    {
        $stats = $this->service->getStats();

        return response()->json([
            'success' => true,
            'message' => 'Admin dashboard stats retrieved successfully.',
            'data'    => new DashboardStatsResource($stats),
            'meta'    => null,
            'errors'  => null,
        ]);
    }
}
