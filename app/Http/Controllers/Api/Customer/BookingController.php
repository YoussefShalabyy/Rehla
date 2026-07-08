<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Customer;

use App\DTOs\Booking\CreateBookingDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Booking\CreateBookingRequest;
use App\Http\Resources\Booking\BookingResource;
use App\Models\Booking;
use App\Models\Listing;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly AvailabilityService $availabilityService
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $bookings = Booking::where('customer_id', $request->user()->id)
            ->with(['listing.media', 'listing.owner'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->paginated($bookings, BookingResource::class);
    }

    public function store(CreateBookingRequest $request): JsonResponse
    {
        // Policy check: Must be a customer to book
        if ($request->user()->cannot('create', Booking::class)) {
            return $this->error('Only customers can create bookings.', 403);
        }

        $dto = CreateBookingDTO::fromRequest($request);

        $booking = $this->bookingService->createBooking($dto, $request->user());

        return $this->created(new BookingResource($booking), 'Booking created successfully.');
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $booking = $this->bookingService->findByUuid($uuid);

        if ($request->user()->cannot('view', $booking)) {
            return $this->error('Unauthorized to view this booking.', 403);
        }

        $booking->load(['listing.media', 'listing.owner']);

        return $this->success(new BookingResource($booking));
    }

    public function cancel(Request $request, string $uuid): JsonResponse
    {
        $booking = $this->bookingService->findByUuid($uuid);

        if ($request->user()->cannot('cancel', $booking)) {
            return $this->error('Unauthorized to cancel this booking.', 403);
        }

        $reason = $request->input('reason', 'Cancelled by customer.');

        $cancelledBooking = $this->bookingService->cancelBooking($booking, $request->user(), $reason);

        return $this->success(new BookingResource($cancelledBooking), 'Booking cancelled successfully.');
    }

    public function availability(string $listingUuid, Request $request): JsonResponse
    {
        $listing = Listing::where('uuid', $listingUuid)->firstOrFail();
        
        $month = $request->query('month', date('Y-m')); // e.g., 2026-08

        $blockedDates = $this->availabilityService->getBlockedDates($listing, $month);

        return $this->success(['blocked_dates' => $blockedDates]);
    }

    public function reschedule(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'check_in_date'  => ['required', 'date'],
            'check_out_date' => ['required', 'date', 'after:check_in_date'],
        ]);

        $booking = $this->bookingService->findByUuid($uuid);

        if ($request->user()->cannot('view', $booking)) {
            return $this->error('Unauthorized to reschedule this booking.', 403);
        }

        $rescheduledBooking = $this->bookingService->rescheduleBooking(
            $booking,
            $request->input('check_in_date'),
            $request->input('check_out_date'),
            $request->user()
        );

        $rescheduledBooking->load(['listing.media', 'listing.owner']);

        return $this->success(new BookingResource($rescheduledBooking), 'Booking rescheduled successfully.');
    }
}
