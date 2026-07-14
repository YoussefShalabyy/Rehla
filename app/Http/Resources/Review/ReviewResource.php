<?php

declare(strict_types=1);

namespace App\Http\Resources\Review;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
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
            'rating'         => $this->rating,
            'comment'        => $this->comment,
            'owner_reply'    => $this->owner_reply,
            'owner_reply_at' => $this->owner_reply_at?->toISOString(),
            'reviewer'       => [
                'name'       => $this->reviewer ? $this->reviewer->name : ($this->reviewer_name ?? 'Anonymous'),
                'avatar_url' => $this->reviewer ? $this->reviewer->avatar_url : null,
            ],
            'status'         => $this->status,
            'listing'        => $this->whenLoaded('listing', fn () => [
                'uuid'  => $this->listing->uuid,
                'title' => $this->listing->title,
            ]),
            'created_at'     => $this->created_at->toISOString(),
        ];
    }
}
