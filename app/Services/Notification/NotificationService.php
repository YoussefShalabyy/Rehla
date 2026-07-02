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
    public function __construct(private PushNotificationInterface $push) {}

    public function notifyBookingConfirmed(Booking $booking): void
    {
        $booking->loadMissing(['customer', 'listing.owner']);

        // 1. Notify Customer (Push + Email + DB)
        $booking->customer->notify(new BookingConfirmedNotification($booking));

        if ($booking->customer->email) {
            Mail::to($booking->customer->email)->queue(new BookingConfirmedMail($booking));
        }
        
        $customerToken = $booking->customer->expo_push_token ?? 'ExponenPushToken[dummy]';
        SendPushNotification::dispatch(
            $customerToken,
            'Booking Confirmed!',
            "Your booking at {$booking->listing->title} has been confirmed.",
            ['booking_uuid' => $booking->uuid]
        );

        // 2. Notify Owner (Push + DB)
        $booking->listing->owner->notify(new BookingConfirmedNotification($booking));

        $ownerToken = $booking->listing->owner->expo_push_token ?? 'ExponenPushToken[dummy]';
        SendPushNotification::dispatch(
            $ownerToken,
            'New Booking Confirmed!',
            "A new booking has been confirmed for {$booking->listing->title}.",
            ['booking_uuid' => $booking->uuid]
        );
    }

    public function notifyBookingCancelled(Booking $booking): void
    {
        $booking->loadMissing(['customer', 'listing.owner']);

        // 1. Notify Customer (Push + Email + DB)
        $booking->customer->notify(new BookingCancelledNotification($booking));

        if ($booking->customer->email) {
            Mail::to($booking->customer->email)->queue(new BookingCancelledMail($booking));
        }

        $customerToken = $booking->customer->expo_push_token ?? 'ExponenPushToken[dummy]';
        SendPushNotification::dispatch(
            $customerToken,
            'Booking Cancelled',
            "Your booking at {$booking->listing->title} has been cancelled.",
            ['booking_uuid' => $booking->uuid]
        );

        // 2. Notify Owner (Push + DB)
        $booking->listing->owner->notify(new BookingCancelledNotification($booking));

        $ownerToken = $booking->listing->owner->expo_push_token ?? 'ExponenPushToken[dummy]';
        SendPushNotification::dispatch(
            $ownerToken,
            'Booking Cancelled',
            "A booking for {$booking->listing->title} has been cancelled.",
            ['booking_uuid' => $booking->uuid]
        );
    }

    public function notifyNewBooking(Booking $booking): void
    {
        $booking->loadMissing(['listing.owner']);

        // 1. Notify Owner (Push + DB)
        $booking->listing->owner->notify(new NewBookingNotification($booking));

        $ownerToken = $booking->listing->owner->expo_push_token ?? 'ExponenPushToken[dummy]';
        SendPushNotification::dispatch(
            $ownerToken,
            'New Booking Request',
            "You have received a new booking request for {$booking->listing->title}.",
            ['booking_uuid' => $booking->uuid]
        );
    }

    public function notifyCheckInReminder(Booking $booking): void
    {
        $booking->loadMissing(['customer', 'listing']);

        // 1. Notify Customer (Push + Email + DB)
        $booking->customer->notify(new CheckInReminderNotification($booking));

        if ($booking->customer->email) {
            Mail::to($booking->customer->email)->queue(new CheckInReminderMail($booking));
        }

        $customerToken = $booking->customer->expo_push_token ?? 'ExponenPushToken[dummy]';
        SendPushNotification::dispatch(
            $customerToken,
            'Check-in Reminder',
            "Your check-in at {$booking->listing->title} is tomorrow!",
            ['booking_uuid' => $booking->uuid]
        );
    }
}
