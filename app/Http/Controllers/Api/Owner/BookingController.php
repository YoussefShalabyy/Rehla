<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Http\Resources\Booking\BookingResource;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        // Get all bookings for listings owned by this provider
        $bookings = Booking::whereHas('listing', function ($query) use ($request) {
                $query->where('owner_id', $request->user()->id);
            })
            ->with(['listing', 'customer'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->paginated($bookings, BookingResource::class);
    }
}
