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
            'total_users'         => $this['total_users'] ?? 0,
            'total_bookings'      => $this['total_bookings'] ?? 0,
            'listings_by_status'  => $this['listings_by_status'] ?? [],
            'bookings_by_status'  => $this['bookings_by_status'] ?? [],
            'total_revenue_cents'          => $this['total_revenue_cents'] ?? 0,
            'gross_sales_cents'            => $this['gross_sales_cents'] ?? 0,
            'profit_from_cars_cents'       => $this['profit_from_cars_cents'] ?? 0,
            'profit_from_properties_cents' => $this['profit_from_properties_cents'] ?? 0,
        ];
    }
}
