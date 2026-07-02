<?php

declare(strict_types=1);

namespace App\Http\Resources\Payment;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid'           => $this->uuid,
            'booking_uuid'   => $this->whenLoaded('booking', fn() => $this->booking->uuid),
            'amount_cents'   => $this->amount_cents,
            'currency'       => 'EGP',
            'gateway'        => $this->gateway->value,
            'status'         => $this->status->value,
            'payment_method' => $this->payment_method,
            'created_at'     => $this->created_at->toIso8601String(),
        ];
    }
}
