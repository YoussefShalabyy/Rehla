<?php

namespace Database\Factories;

use App\Models\Payment;
use App\Models\Booking;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'uuid'           => (string) Str::uuid(),
            'booking_id'     => Booking::factory(),
            'amount_cents'   => $this->faker->numberBetween(50000, 500000),
            'status'                 => PaymentStatus::Pending,
            'gateway_transaction_id' => $this->faker->uuid(),
            'gateway'                => 'paymob',
        ];
    }

    public function succeeded(): static
    {
        return $this->state(['status' => PaymentStatus::Succeeded]);
    }

    public function failed(): static
    {
        return $this->state(['status' => PaymentStatus::Failed]);
    }

    public function refunded(): static
    {
        return $this->state(['status' => PaymentStatus::Refunded]);
    }
}
