<?php

declare(strict_types=1);

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DashboardStatsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'total_users'             => $this['total_users'] ?? 0,
            'listings_by_status'      => $this['listings_by_status'] ?? [],
            'bookings_by_status'      => $this['bookings_by_status'] ?? [],
            'total_revenue_cents'     => $this['total_revenue_cents'] ?? 0,
            'pending_approvals_count' => $this['pending_approvals_count'] ?? 0,
        ];
    }
}
