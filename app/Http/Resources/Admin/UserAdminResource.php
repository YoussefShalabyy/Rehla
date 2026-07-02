<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserAdminResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid'           => $this->uuid,
            'name'           => $this->name,
            'email'          => $this->email,
            'role'           => $this->role,
            'status'         => $this->status,
            'created_at'     => $this->created_at,
            'listings_count' => $this->whenCounted('listings'),
            'bookings_count' => $this->whenCounted('bookings'),
        ];
    }
}
