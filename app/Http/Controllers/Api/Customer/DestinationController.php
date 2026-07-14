<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Destination;
use Illuminate\Http\JsonResponse;

class DestinationController extends Controller
{
    /**
     * Get suggested destinations
     */
    public function suggested(): JsonResponse
    {
        $destinations = Destination::where('is_active', true)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Suggested destinations retrieved successfully.',
            'data'    => $destinations,
            'meta'    => null,
            'errors'  => null,
        ]);
    }
}
