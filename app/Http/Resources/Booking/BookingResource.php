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
            'is_reviewed'        => $this->relationLoaded('review') ? $this->review !== null : $this->review()->exists(),
            'created_at'         => $this->created_at->toIso8601String(),
        ];

        // If listing is loaded, return it but avoiding a massive response if not fully eager loaded
        if ($this->relationLoaded('listing')) {
            $primaryMedia = null;
            if ($this->listing->relationLoaded('media')) {
                $primaryMedia = $this->listing->media->where('is_primary', true)->first() ?? $this->listing->media->first();
            }

            $data['listing'] = [
                'uuid'  => $this->listing->uuid,
                'title' => $this->listing->title,
                'city'  => $this->listing->city,
                'country' => $this->listing->country ?? 'Egypt',
                'type' => $this->listing->type,
                'primary_image_url' => $primaryMedia ? $primaryMedia->url : 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?auto=format&fit=crop&q=80&w=800',
            ];
        }

        // Include pricing snapshot if loaded
        if (isset($this->pricing_snapshot)) {
            $data['pricing_snapshot'] = $this->pricing_snapshot;
        }

        return $data;
    }
}
