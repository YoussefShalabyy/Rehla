<?php

declare(strict_types=1);

namespace App\DTOs\Payment;

use App\Enums\PaymentGateway;

readonly class InitiatePaymentDTO
{
    public function __construct(
        public string $bookingUuid,
        public PaymentGateway $gateway,
    ) {
    }
}
