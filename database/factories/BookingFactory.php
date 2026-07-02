<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Carbon\Carbon;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $checkIn = Carbon::today()->addDays(rand(1, 10));
        $checkOut = $checkIn->copy()->addDays(rand(1, 5));

        return [
            'listing_id' => Listing::factory(),
            'customer_id' => User::factory(),
            'check_in_date' => $checkIn->format('Y-m-d'),
            'check_out_date' => $checkOut->format('Y-m-d'),
            'guests_count' => rand(1, 4),
            'currency' => 'EGP',
            'total_amount_cents' => rand(1000, 5000) * 100,
            'platform_fee_cents' => rand(100, 500) * 100,
            'status' => 'pending',
            'payment_status' => 'pending',
        ];
    }
}
