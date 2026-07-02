<?php

namespace Tests\Unit\Services;

use App\Console\Commands\SendCheckInReminders;
use App\Enums\BookingStatus;
use App\Jobs\SendPushNotification;
use App\Mail\BookingConfirmedMail;
use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use App\Services\Notification\NotificationService;
use App\Interfaces\PushNotificationInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

class NotificationServiceTest extends TestCase
{
    use RefreshDatabase;

    private NotificationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $pushMock = $this->createMock(PushNotificationInterface::class);
        $this->service = new NotificationService($pushMock);
    }

    public function test_dispatches_push_job_and_mail_when_booking_is_confirmed()
    {
        Queue::fake();
        Mail::fake();

        $customer = User::factory()->create(['role' => 'customer']);
        $owner = User::factory()->create(['role' => 'provider']);
        $listing = Listing::factory()->create(['owner_id' => $owner->id]);
        $booking = Booking::factory()->create([
            'customer_id' => $customer->id,
            'listing_id'  => $listing->id,
            'status'      => BookingStatus::Confirmed,
        ]);

        $this->service->notifyBookingConfirmed($booking);

        Queue::assertPushed(SendPushNotification::class, 2); // Customer + Owner
        Mail::assertQueued(BookingConfirmedMail::class, 1); // Customer email
    }

    public function test_push_failure_does_not_throw_exception_to_caller()
    {
        // Testing the Job isolation rule
        $failingPushMock = $this->createMock(PushNotificationInterface::class);
        $failingPushMock->method('sendBulk')->willThrowException(new \Exception('Expo API down'));

        $job = new SendPushNotification(['token'], 'title', 'body');

        // Capture logs to ensure Log::warning is called
        Log::shouldReceive('warning')->once()->withArgs(function($message) {
            return str_contains($message, 'SendPushNotification Job failed: Expo API down');
        });

        // The job handle should catch the exception and log it, rather than throwing
        try {
            $job->handle($failingPushMock);
            $this->assertTrue(true); // Should reach here
        } catch (\Throwable $e) {
            $this->fail('Exception was thrown: ' . $e->getMessage());
        }
    }

    public function test_check_in_reminder_command_dispatches_jobs_for_tomorrow_bookings()
    {
        Queue::fake();

        $customer = User::factory()->create(['role' => 'customer']);
        $listing = Listing::factory()->create();
        
        $tomorrow = \Carbon\Carbon::tomorrow()->toDateString();
        $today = \Carbon\Carbon::today()->toDateString();

        // Should be notified
        $booking1 = Booking::factory()->create([
            'customer_id'   => $customer->id,
            'listing_id'    => $listing->id,
            'status'        => BookingStatus::Confirmed,
            'check_in_date' => $tomorrow,
        ]);

        // Should NOT be notified (wrong date)
        Booking::factory()->create([
            'customer_id'   => $customer->id,
            'listing_id'    => $listing->id,
            'status'        => BookingStatus::Confirmed,
            'check_in_date' => $today,
        ]);

        // Should NOT be notified (wrong status)
        Booking::factory()->create([
            'customer_id'   => $customer->id,
            'listing_id'    => $listing->id,
            'status'        => BookingStatus::Pending,
            'check_in_date' => $tomorrow,
        ]);

        $this->artisan('app:send-check-in-reminders')
            ->expectsOutputToContain('Dispatched check-in reminders for 1 bookings.')
            ->assertExitCode(0);
        
        Queue::assertPushed(SendPushNotification::class, 1); // Only for booking1
    }
}
