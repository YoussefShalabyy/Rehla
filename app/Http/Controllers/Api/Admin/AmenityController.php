<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use Illuminate\Http\JsonResponse;

class AmenityController extends Controller
{
    public function index(): JsonResponse
    {
        $amenities = Amenity::all(['id', 'name', 'icon', 'type']);
        
        return $this->success($amenities, 'Amenities retrieved successfully.');
    }
}
