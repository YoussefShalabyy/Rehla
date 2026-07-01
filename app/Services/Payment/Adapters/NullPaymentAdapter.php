<?php

declare(strict_types=1);

namespace App\Services\Payment\Adapters;

use App\Interfaces\PaymentGatewayInterface;

/**
 * NullPaymentAdapter — used in tests and local development.
 * Never calls any real payment API.
 * Always returns a successful response.
 */
final class NullPaymentAdapter implements PaymentGatewayInterface
{
    public function charge(array $payload): array
    {
        return [
            'success'      => true,
            'transaction_id' => 'null-txn-' . uniqid(),
            'checkout_url' => null,
            'raw'          => ['adapter' => 'null'],
        ];
    }

    public function refund(string $transactionId, int $amountCents): array
    {
        return [
            'success' => true,
            'raw'     => ['adapter' => 'null', 'refunded_cents' => $amountCents],
        ];
    }

    public function verifyWebhook(array $payload, string $signature): bool
    {
        // In tests, pass 'valid-signature' as the signature to get true
        return $signature === 'valid-signature';
    }
}
