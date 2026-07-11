<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Interfaces\PushNotificationInterface;
use App\Jobs\SendPushNotification;
use App\Mail\BookingCancelledMail;
use App\Mail\BookingConfirmedMail;
use App\Mail\CheckInReminderMail;
use App\Notifications\BookingCancelledNotification;
use App\Notifications\BookingConfirmedNotification;
use App\Notifications\CheckInReminderNotification;
use App\Notifications\NewBookingNotification;
use App\Models\Booking;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function __construct(private PushNotificationInterface $push)
    {
    }

    /**
     * Notify customer when their booking is confirmed.
     * (Admin-owned listings — no owner notification.)
     */
    public function notifyBookingConfirmed(Booking $booking): void
    {
        $booking->loadMissing(['customer', 'listing']);

        // Notify Customer (Push + Email + DB)
        $booking->customer->notify(new BookingConfirmedNotification($booking));

        if ($booking->customer->email) {
            Mail::to($booking->customer->email)->queue(new BookingConfirmedMail($booking));
        }

        $customerToken = $booking->customer->expo_push_token ?? 'ExponentPushToken[dummy]';
        SendPushNotification::dispatch(
            $customerToken,
            'Booking Confirmed!',
            "Your booking at {$booking->listing->title} has been confirmed.",
            ['booking_uuid' => $booking->uuid]
        );
    }

    /**
     * Notify customer when their booking is cancelled.
     */
    public function notifyBookingCancelled(Booking $booking): void
    {
        $booking->loadMissing(['customer', 'listing']);

        // Notify Customer (Push + Email + DB)
        $booking->customer->notify(new BookingCancelledNotification($booking));

        if ($booking->customer->email) {
            Mail::to($booking->customer->email)->queue(new BookingCancelledMail($booking));
        }

        $customerToken = $booking->customer->expo_push_token ?? 'ExponentPushToken[dummy]';
        SendPushNotification::dispatch(
            $customerToken,
            'Booking Cancelled',
            "Your booking at {$booking->listing->title} has been cancelled.",
            ['booking_uuid' => $booking->uuid]
        );
    }

    /**
     * Notify customer when a new booking is created (pending confirmation).
     */
    public function notifyNewBooking(Booking $booking): void
    {
        $booking->loadMissing(['customer', 'listing']);

        // Notify Customer (DB only — they initiated the booking)
        $booking->customer->notify(new NewBookingNotification($booking));
    }

    /**
     * Notify customer about an upcoming check-in.
     */
    public function notifyCheckInReminder(Booking $booking): void
    {
        $booking->loadMissing(['customer', 'listing']);

        // Notify Customer (Push + Email + DB)
        $booking->customer->notify(new CheckInReminderNotification($booking));

        if ($booking->customer->email) {
            Mail::to($booking->customer->email)->queue(new CheckInReminderMail($booking));
        }

        $customerToken = $booking->customer->expo_push_token ?? 'ExponentPushToken[dummy]';
        SendPushNotification::dispatch(
            $customerToken,
            'Check-in Reminder',
            "Your check-in at {$booking->listing->title} is tomorrow!",
            ['booking_uuid' => $booking->uuid]
        );
    }
}
