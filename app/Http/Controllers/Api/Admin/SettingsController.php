<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\PlatformSettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function __construct(private PlatformSettingsService $service) {}

    public function index(): JsonResponse
    {
        $settings = $this->service->all();

        return response()->json([
            'success' => true,
            'message' => 'Platform settings retrieved successfully.',
            'data'    => $settings,
            'meta'    => null,
            'errors'  => null,
        ]);
    }

    public function update(Request $request, string $key): JsonResponse
    {
        $validated = $request->validate([
            'value' => 'required',
        ]);

        try {
            $setting = $this->service->update($key, $validated['value']);

            return response()->json([
                'success' => true,
                'message' => 'Platform setting updated successfully.',
                'data'    => $setting,
                'meta'    => null,
                'errors'  => null,
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'data'    => null,
                'meta'    => null,
                'errors'  => null,
            ], 422);
        }
    }
}
