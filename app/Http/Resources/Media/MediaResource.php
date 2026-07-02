<?php

namespace App\Http\Resources\Media;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid'       => $this->uuid,
            'url'        => $this->url,
            'type'       => $this->type,
            'is_primary' => (bool) $this->is_primary,
            'order'      => $this->order,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
