<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:send-check-in-reminders')]
#[Description('Send check-in reminders for bookings starting tomorrow')]
class SendCheckInReminders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(\App\Services\Notification\NotificationService $notificationService)
    {
        $tomorrow = \Carbon\Carbon::tomorrow()->toDateString();
        
        $bookings = \App\Models\Booking::whereDate('check_in_date', $tomorrow)
            ->where('status', \App\Enums\BookingStatus::Confirmed)
            ->get();

        foreach ($bookings as $booking) {
            $notificationService->notifyCheckInReminder($booking);
        }

        $this->info("Dispatched check-in reminders for {$bookings->count()} bookings.");
    }
}
