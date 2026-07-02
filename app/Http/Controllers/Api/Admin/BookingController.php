<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\BookingStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Booking\BookingResource;
use App\Models\Booking;
use App\Services\Booking\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $query = Booking::with(['listing', 'customer'])->orderBy('created_at', 'desc');

        if ($request->has('status')) {
            $query->where('status', $request->query('status'));
        }

        $bookings = $query->paginate(20);

        return $this->paginated($bookings, BookingResource::class);
    }

    public function updateStatus(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'status' => ['required', Rule::enum(BookingStatus::class)],
        ]);

        $booking = $this->bookingService->findByUuid($uuid);
        
        // Direct override by admin
        $booking->update([
            'status' => BookingStatus::from($request->input('status')),
        ]);

        return $this->success(new BookingResource($booking), 'Booking status updated by Admin.');
    }
}
