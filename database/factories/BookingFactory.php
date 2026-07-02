<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\User;
use App\Models\Listing;
use App\Enums\UserRole;
use App\Enums\BookingStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class BookingFactory extends Factory
{
    protected $model = Booking::class;

    public function definition(): array
    {
        $checkIn = $this->faker->dateTimeBetween('+1 days', '+30 days')->format('Y-m-d');
        $checkOut = $this->faker->dateTimeBetween('+31 days', '+60 days')->format('Y-m-d');

        return [
            'uuid'               => (string) Str::uuid(),
            'booking_reference'  => strtoupper(Str::random(8)),
            'customer_id'        => User::factory()->create(['role' => UserRole::Customer])->id,
            'listing_id'         => Listing::factory()->published(),
            'check_in_date'      => $checkIn,
            'check_out_date'     => $checkOut,
            'total_amount_cents' => $this->faker->numberBetween(50000, 500000), // 500 to 5000 EGP
            'platform_fee_cents' => 5000,
            'status'             => BookingStatus::Pending,
            'guests_count'       => 2,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(['status' => BookingStatus::Confirmed]);
    }

    public function completed(): static
    {
        return $this->state(['status' => BookingStatus::Completed]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => BookingStatus::Cancelled]);
    }
}
