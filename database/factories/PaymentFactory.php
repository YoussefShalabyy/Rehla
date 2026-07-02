<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'booking_id' => Booking::factory(),
            'gateway' => 'paymob',
            'amount_cents' => rand(1000, 5000) * 100,
            'status' => 'pending',
            'gateway_transaction_id' => (string) rand(100000, 999999),
        ];
    }
}
