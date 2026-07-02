<?php

declare(strict_types=1);

namespace App\Http\Resources\Booking;

use App\Http\Resources\Listing\ListingResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // $this->resource is the Booking model
        $data = [
            'uuid'               => $this->uuid,
            'booking_reference'  => $this->booking_reference,
            'status'             => $this->status->value,
            'payment_status'     => $this->payment_status->value,
            'check_in_date'      => $this->check_in_date->format('Y-m-d'),
            'check_out_date'     => $this->check_out_date->format('Y-m-d'),
            'guests_count'       => $this->guests_count,
            'total_amount_cents' => $this->total_amount_cents,
            'platform_fee_cents' => $this->platform_fee_cents,
            'currency'           => $this->currency,
            'notes'              => $this->notes,
            'created_at'         => $this->created_at->toIso8601String(),
        ];

        // If listing is loaded, return it but avoiding a massive response if not fully eager loaded
        if ($this->relationLoaded('listing')) {
            $data['listing'] = [
                'uuid'  => $this->listing->uuid,
                'title' => $this->listing->title,
                'city'  => $this->listing->city,
            ];
        }

        // Include pricing breakdown if dynamically attached (on creation)
        if (isset($this->pricing_breakdown)) {
            $data['pricing_breakdown'] = $this->pricing_breakdown;
        }

        return $data;
    }
}
