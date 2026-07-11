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
            'phone'          => $this->phone,
            'role'           => $this->role,
            'status'         => $this->status,
            'created_at'     => $this->created_at,
            'bookings_count' => $this->whenCounted('bookings'),
        ];
    }
}
