<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Services\Owner\OwnerDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private OwnerDashboardService $service) {}

    public function stats(Request $request): JsonResponse
    {
        $stats = $this->service->getStats($request->user());

        return response()->json([
            'success' => true,
            'message' => 'Owner dashboard stats retrieved successfully.',
            'data'    => $stats,
            'meta'    => null,
            'errors'  => null,
        ]);
    }
}
