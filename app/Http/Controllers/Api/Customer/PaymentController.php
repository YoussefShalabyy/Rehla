<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Customer;

use App\DTOs\Payment\InitiatePaymentDTO;
use App\Enums\PaymentGateway;
use App\Http\Controllers\Controller;
use App\Http\Resources\Payment\PaymentResource;
use App\Models\Booking;
use App\Models\Payment;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function initiate(Request $request): JsonResponse
    {
        $request->validate([
            'booking_uuid' => ['required', 'string', 'exists:bookings,uuid'],
            'gateway'      => ['required', Rule::enum(PaymentGateway::class)],
        ]);

        $booking = Booking::where('uuid', $request->input('booking_uuid'))->firstOrFail();

        if ($request->user()->cannot('create', [Payment::class, $booking])) {
            return $this->error('Unauthorized to pay for this booking.', 403);
        }

        $dto = new InitiatePaymentDTO(
            bookingUuid: $booking->uuid,
            gateway: PaymentGateway::from($request->input('gateway'))
        );

        try {
            $result = $this->paymentService->initiatePayment($dto, $request->user());

            return response()->json([
                'success' => true,
                'message' => 'Payment initiated successfully.',
                'data' => [
                    'payment' => new PaymentResource($result['payment']),
                    'checkout_url' => $result['checkout_url'],
                ],
                'errors' => null,
                'meta' => null,
            ], 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 422);
        }
    }

    public function show(Request $request, string $uuid): JsonResponse
    {
        $payment = Payment::where('uuid', $uuid)->with('booking')->firstOrFail();

        if ($request->user()->cannot('view', $payment)) {
            return $this->error('Unauthorized.', 403);
        }

        return $this->success(new PaymentResource($payment));
    }

    public function history(Request $request): JsonResponse
    {
        $payments = Payment::whereHas('booking', function ($query) use ($request) {
                $query->where('customer_id', $request->user()->id);
            })
            ->with('booking')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return $this->paginated($payments, PaymentResource::class);
    }
}
