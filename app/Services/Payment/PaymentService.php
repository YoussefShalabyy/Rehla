<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\DTOs\Payment\InitiatePaymentDTO;
use App\Enums\BookingStatus;
use App\Enums\PaymentStatus;
use App\Interfaces\PaymentGatewayInterface;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Str;
use App\Services\Notification\NotificationService;

class PaymentService
{
    public function __construct(
        private PaymentGatewayInterface $gateway,
        private NotificationService $notificationService
    ) {}

    /**
     * Initiate a payment for a booking.
     * Returns an array with ['payment' => Payment, 'checkout_url' => string]
     */
    public function initiatePayment(InitiatePaymentDTO $dto, User $customer): array
    {
        $booking = Booking::where('uuid', $dto->bookingUuid)->first();

        if (! $booking) {
            throw new NotFoundHttpException('Booking not found.');
        }

        if ($booking->customer_id !== $customer->id) {
            throw new HttpException(403, 'Unauthorized to pay for this booking.');
        }

        if ($booking->status !== BookingStatus::Pending) {
            throw new HttpException(422, 'Only pending bookings can be paid for.');
        }

        if ($booking->payment_status === \App\Enums\PaymentStatus::Paid) {
            throw new HttpException(422, 'Booking is already paid.');
        }

        $payment = Payment::create([
            'uuid' => Str::uuid()->toString(),
            'booking_id' => $booking->id,
            'gateway' => $dto->gateway,
            'amount_cents' => $booking->total_amount_cents,
            'status' => PaymentStatus::Pending,
        ]);

        $payload = [
            'amount_cents' => $booking->total_amount_cents,
            'currency' => $booking->currency,
            'booking_reference' => $booking->booking_reference,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_phone' => '+201000000000', // In a real app, read from User profile
        ];

        $gatewayResult = $this->gateway->charge($payload);

        if (! $gatewayResult['success']) {
            $payment->update([
                'status' => PaymentStatus::Failed,
                'provider_response' => $gatewayResult['raw'],
            ]);
            throw new \Exception('Payment initiation failed with gateway.');
        }

        $payment->update([
            'gateway_transaction_id' => $gatewayResult['transaction_id'],
            'provider_response' => $gatewayResult['raw'],
        ]);

        return [
            'payment' => $payment,
            'checkout_url' => $gatewayResult['checkout_url'],
        ];
    }

    /**
     * Handle incoming webhooks from the payment gateway idempotently.
     */
    public function handleWebhook(array $payload, string $signature): void
    {
        // 1. Verify Signature
        if (! $this->gateway->verifyWebhook($payload, $signature)) {
            throw new \Exception('Invalid Webhook Signature');
        }

        $obj = $payload['obj'] ?? [];
        $transactionId = (string) ($obj['order']['id'] ?? '');
        $success = $obj['success'] ?? false;

        if (empty($transactionId)) {
            throw new \Exception('Invalid Webhook Payload: Missing Order ID');
        }

        DB::transaction(function () use ($transactionId, $success, $payload) {
            // Find payment by transaction ID and lock for update to prevent race conditions
            $payment = Payment::where('gateway_transaction_id', $transactionId)->lockForUpdate()->first();

            if (! $payment) {
                // Payment might not be created yet or wrong ID, ignore
                return;
            }

            // 3. Idempotency Check
            if ($payment->status !== PaymentStatus::Pending) {
                return; // Already processed
            }

            if ($success) {
                $payment->update([
                    'status' => PaymentStatus::Paid,
                    'provider_response' => $payload,
                ]);

                // Update Booking Status
                $payment->booking->update([
                    'status' => BookingStatus::Confirmed,
                    'payment_status' => \App\Enums\PaymentStatus::Paid,
                ]);
                $this->notificationService->notifyBookingConfirmed($payment->booking);
            } else {
                $payment->update([
                    'status' => PaymentStatus::Failed,
                    'provider_response' => $payload,
                ]);
                
                // Booking remains pending so the user can try again
            }
        });
    }

    /**
     * Process a refund for a successful payment.
     */
    public function processRefund(Payment $payment): Payment
    {
        if ($payment->status !== PaymentStatus::Paid) {
            throw new HttpException(422, 'Only succeeded payments can be refunded.');
        }

        $gatewayResult = $this->gateway->refund($payment->gateway_transaction_id, $payment->amount_cents);

        if (! $gatewayResult['success']) {
            throw new \Exception('Refund failed with gateway.');
        }

        $payment->update([
            'status' => PaymentStatus::Refunded,
        ]);

        $payment->booking->update([
            'status' => BookingStatus::Cancelled,
            'payment_status' => \App\Enums\PaymentStatus::Refunded,
        ]);

        return $payment;
    }
}
