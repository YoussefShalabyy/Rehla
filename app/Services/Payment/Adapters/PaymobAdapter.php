<?php

declare(strict_types=1);

namespace App\Services\Payment\Adapters;

use App\Interfaces\PaymentGatewayInterface;
use Illuminate\Support\Facades\Http;
use Exception;

class PaymobAdapter implements PaymentGatewayInterface
{
    private string $apiKey;
    private string $integrationId;
    private string $hmacSecret;
    private string $baseUrl;

    public function __construct()
    {
        $this->apiKey = config('payment.paymob.api_key');
        $this->integrationId = config('payment.paymob.integration_id');
        $this->hmacSecret = config('payment.paymob.hmac_secret');
        $this->baseUrl = config('payment.paymob.base_url');
    }

    public function charge(array $payload): array
    {
        try {
            // 1. Authentication Request
            $authResponse = Http::post("{$this->baseUrl}/auth/tokens", [
                'api_key' => $this->apiKey,
            ]);

            if (! $authResponse->successful()) {
                throw new Exception('Paymob Auth Failed');
            }
            $token = $authResponse->json('token');

            // 2. Order Registration Request
            $orderResponse = Http::post("{$this->baseUrl}/ecommerce/orders", [
                'auth_token' => $token,
                'delivery_needed' => 'false',
                'amount_cents' => (string) $payload['amount_cents'],
                'currency' => $payload['currency'] ?? 'EGP',
                'merchant_order_id' => $payload['booking_reference'] . '-' . time(),
                'items' => [],
            ]);

            if (! $orderResponse->successful()) {
                throw new Exception('Paymob Order Registration Failed');
            }
            $orderId = $orderResponse->json('id');

            // 3. Payment Key Request
            $paymentKeyResponse = Http::post("{$this->baseUrl}/acceptance/payment_keys", [
                'auth_token' => $token,
                'amount_cents' => (string) $payload['amount_cents'],
                'expiration' => 3600,
                'order_id' => $orderId,
                'billing_data' => [
                    'apartment' => 'NA',
                    'email' => $payload['customer_email'] ?? 'test@example.com',
                    'floor' => 'NA',
                    'first_name' => $payload['customer_name'] ?? 'John',
                    'street' => 'NA',
                    'building' => 'NA',
                    'phone_number' => $payload['customer_phone'] ?? '+201000000000',
                    'shipping_method' => 'NA',
                    'postal_code' => 'NA',
                    'city' => 'NA',
                    'country' => 'EG',
                    'last_name' => 'Doe',
                    'state' => 'NA',
                ],
                'currency' => $payload['currency'] ?? 'EGP',
                'integration_id' => $this->integrationId,
            ]);

            if (! $paymentKeyResponse->successful()) {
                throw new Exception('Paymob Payment Key Generation Failed');
            }
            $paymentToken = $paymentKeyResponse->json('token');

            // 4. Construct Checkout URL
            // Paymob standalone checkout URL format:
            $checkoutUrl = "https://accept.paymob.com/api/acceptance/iframes/" . config('payment.paymob.iframe_id') . "?payment_token={$paymentToken}";

            return [
                'success' => true,
                'transaction_id' => (string) $orderId,
                'checkout_url' => $checkoutUrl,
                'raw' => [
                    'order_id' => $orderId,
                    'payment_token' => $paymentToken,
                ],
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'transaction_id' => null,
                'checkout_url' => null,
                'raw' => ['error' => $e->getMessage()],
            ];
        }
    }

    public function refund(string $transactionId, int $amountCents): array
    {
        try {
            // Paymob refund logic
            $authResponse = Http::post("{$this->baseUrl}/auth/tokens", [
                'api_key' => $this->apiKey,
            ]);
            $token = $authResponse->json('token');

            $refundResponse = Http::post("{$this->baseUrl}/acceptance/void_refund/refund", [
                'auth_token' => $token,
                'transaction_id' => $transactionId,
                'amount_cents' => $amountCents,
            ]);

            return [
                'success' => $refundResponse->successful(),
                'raw' => $refundResponse->json(),
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'raw' => ['error' => $e->getMessage()],
            ];
        }
    }

    public function verifyWebhook(array $payload, string $signature): bool
    {
        $obj = $payload['obj'] ?? [];

        // Paymob concatenates specific fields in order to generate the HMAC
        $connectedString = '';
        $keys = [
            'amount_cents',
            'created_at',
            'currency',
            'error_occured',
            'has_parent_transaction',
            'id',
            'integration_id',
            'is_3d_secure',
            'is_auth',
            'is_capture',
            'is_refunded',
            'is_standalone_payment',
            'is_voided',
            'order.id',
            'owner',
            'pending',
            'source_data.pan',
            'source_data.sub_type',
            'source_data.type',
            'success',
        ];

        foreach ($keys as $key) {
            if (str_contains($key, '.')) {
                $parts = explode('.', $key);
                $value = $obj[$parts[0]][$parts[1]] ?? '';
            } else {
                $value = $obj[$key] ?? '';
            }

            // Paymob boolean values in HMAC calculation are 'true'/'false' as strings
            if (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            $connectedString .= $value;
        }

        $calculatedHmac = hash_hmac('sha512', $connectedString, $this->hmacSecret);

        return hash_equals($calculatedHmac, $signature);
    }
}
