<?php

declare(strict_types=1);

namespace App\Services\Booking;

use App\DTOs\Booking\CreateBookingDTO;
use App\DTOs\Booking\CancelBookingDTO;
use App\Enums\BookingStatus;
use App\Enums\ListingStatus;
use App\Exceptions\BookingConflictException;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Services\Notification\NotificationService;

class BookingService
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly PricingService $pricingService,
        private readonly NotificationService $notificationService
    ) {
    }

    /**
     * Create a new booking atomically.
     * Prevents race conditions by locking the listing row for update.
     */
    public function createBooking(CreateBookingDTO $dto, User $customer): Booking
    {
        return DB::transaction(function () use ($dto, $customer) {
            // 1. Find Listing and lock it for update
            $listing = Listing::where('uuid', $dto->listingUuid)->lockForUpdate()->first();

            if (! $listing) {
                throw new NotFoundHttpException('Listing not found.');
            }

            if ($listing->status !== ListingStatus::Active) {
                throw new HttpException(422, 'Listing is not available for booking.');
            }

            if ($dto->guestsCount > $listing->max_guests) {
                throw new HttpException(422, 'Guests count exceeds the maximum allowed for this listing.');
            }

            // 2. Check Availability inside the lock
            $isAvailable = $this->availabilityService->isAvailable(
                $listing,
                $dto->checkInDate,
                $dto->checkOutDate
            );

            if (! $isAvailable) {
                throw new BookingConflictException('The requested dates are not available.');
            }

            // 3. Calculate Pricing
            $pricing = $this->pricingService->calculate(
                $listing,
                $dto->checkInDate,
                $dto->checkOutDate,
                $dto->guestsCount
            );

            // 4. Create Booking with persisted pricing snapshot
            $booking = Booking::create([
                'listing_id'         => $listing->id,
                'customer_id'        => $customer->id,
                'check_in_date'      => $dto->checkInDate,
                'check_out_date'     => $dto->checkOutDate,
                'guests_count'       => $dto->guestsCount,
                'total_amount_cents' => $pricing->grandTotalCents,
                'platform_fee_cents' => $pricing->platformFeeCents,
                'notes'              => $dto->notes,
                'status'             => BookingStatus::Pending,
                'payment_status'     => \App\Enums\PaymentStatus::Pending,
                'pricing_snapshot'   => [
                    'nights'                => $pricing->nights,
                    'base_total_cents'      => $pricing->baseTotalCents,
                    'cleaning_fee_cents'    => $pricing->cleaningFeeCents,
                    'extra_guest_fee_cents' => $pricing->extraGuestFeeCents,
                    'platform_fee_cents'    => $pricing->platformFeeCents,
                    'grand_total_cents'     => $pricing->grandTotalCents,
                ],
            ]);

            $this->notificationService->notifyNewBooking($booking);

            return $booking;
        });
    }

    public function findByUuid(string $uuid): Booking
    {
        $booking = Booking::where('uuid', $uuid)->first();
        if (! $booking) {
            throw new NotFoundHttpException('Booking not found.');
        }
        return $booking;
    }

    /**
     * Cancel an existing booking
     */
    public function cancelBooking(Booking $booking, User $requester, string $reason): Booking
    {
        if (in_array($booking->status, [BookingStatus::Completed, BookingStatus::Cancelled])) {
            throw new HttpException(422, 'Booking cannot be cancelled in its current state.');
        }

        $booking->update([
            'status'              => BookingStatus::Cancelled,
            'cancellation_reason' => $reason,
        ]);

        $this->notificationService->notifyBookingCancelled($booking);

        return $booking;
    }

    /**
     * Confirm a booking (called internally after payment or manual confirmation)
     */
    public function confirmBooking(Booking $booking): Booking
    {
        if ($booking->status !== BookingStatus::Pending) {
            throw new HttpException(422, 'Only pending bookings can be confirmed.');
        }

        $booking->update([
            'status' => BookingStatus::Confirmed,
        ]);

        return $booking;
    }

    /**
     * Complete a booking (e.g. after check-out date passes)
     */
    public function completeBooking(Booking $booking): Booking
    {
        if (! in_array($booking->status, [BookingStatus::Confirmed, BookingStatus::Active])) {
            throw new HttpException(422, 'Only confirmed or active bookings can be completed.');
        }

        $booking->update([
            'status' => BookingStatus::Completed,
        ]);

        return $booking;
    }

    /**
     * Reschedule an existing booking
     */
    public function rescheduleBooking(Booking $booking, string $newCheckIn, string $newCheckOut, User $requester): Booking
    {
        if (! in_array($booking->status, [BookingStatus::Pending, BookingStatus::Confirmed])) {
            throw new HttpException(422, 'Only pending or confirmed bookings can be rescheduled.');
        }

        // Recalculate price
        $listing = $booking->listing;
        
        $pricing = $this->pricingService->calculate(
            $listing,
            $newCheckIn,
            $newCheckOut,
            $booking->guests_count
        );

        $booking->update([
            'check_in_date'      => $newCheckIn,
            'check_out_date'     => $newCheckOut,
            'total_amount_cents' => $pricing->grandTotalCents,
            'platform_fee_cents' => $pricing->platformFeeCents,
        ]);

        return $booking;
    }
}
