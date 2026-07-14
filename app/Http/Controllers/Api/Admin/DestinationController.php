<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DestinationController extends Controller
{
    /**
     * Get all destinations (Admin)
     */
    public function index(): JsonResponse
    {
        $destinations = Destination::orderBy('sort_order', 'asc')->get();

        return response()->json([
            'success' => true,
            'message' => 'Destinations retrieved successfully.',
            'data'    => $destinations,
            'meta'    => null,
            'errors'  => null,
        ]);
    }

    /**
     * Create a new destination
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'subtitle'   => 'nullable|string|max:255',
            'icon'       => 'nullable|string|max:255',
            'icon_color' => 'nullable|string|max:255',
            'icon_bg'    => 'nullable|string|max:255',
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ]);

        $destination = Destination::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Destination created successfully.',
            'data'    => $destination,
            'meta'    => null,
            'errors'  => null,
        ], 201);
    }

    /**
     * Update an existing destination
     */
    public function update(Request $request, string $uuid): JsonResponse
    {
        $destination = Destination::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'name'       => 'string|max:255',
            'subtitle'   => 'nullable|string|max:255',
            'icon'       => 'nullable|string|max:255',
            'icon_color' => 'nullable|string|max:255',
            'icon_bg'    => 'nullable|string|max:255',
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
        ]);

        $destination->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Destination updated successfully.',
            'data'    => $destination,
            'meta'    => null,
            'errors'  => null,
        ]);
    }

    /**
     * Delete a destination
     */
    public function destroy(string $uuid): JsonResponse
    {
        $destination = Destination::where('uuid', $uuid)->firstOrFail();
        $destination->delete();

        return response()->json([
            'success' => true,
            'message' => 'Destination deleted successfully.',
            'data'    => null,
            'meta'    => null,
            'errors'  => null,
        ]);
    }
}
