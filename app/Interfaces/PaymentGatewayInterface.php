<?php

declare(strict_types=1);

namespace App\Interfaces;

interface PaymentGatewayInterface
{
    /**
     * Charge a customer and return a result array.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>  Must include: ['success' => bool, 'transaction_id' => string|null, 'checkout_url' => string|null, 'raw' => array]
     */
    public function charge(array $payload): array;

    /**
     * Refund a previous transaction.
     *
     * @return array<string, mixed>  Must include: ['success' => bool, 'raw' => array]
     */
    public function refund(string $transactionId, int $amountCents): array;

    /**
     * Verify that a webhook payload is authentic (HMAC check).
     *
     * @param  array<string, mixed>  $payload
     */
    public function verifyWebhook(array $payload, string $signature): bool;
}
