<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Webhook;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymobWebhookController extends Controller
{
    public function __construct(private readonly PaymentService $paymentService)
    {
    }

    public function handle(Request $request): JsonResponse
    {
        $payload = $request->all();
        $signature = $request->query('hmac', '');

        try {
            $this->paymentService->handleWebhook($payload, $signature);
            
            // Paymob requires a 200 OK response, otherwise they will keep retrying
            return response()->json(['success' => true], 200);
        } catch (\Exception $e) {
            Log::error('Paymob Webhook Failed: ' . $e->getMessage(), [
                'payload' => $payload,
                'signature' => $signature,
            ]);

            // Return 400 so Paymob knows it failed, though depending on the error we might want to return 200 to stop retries if it's our fault.
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }
}
