<?php

namespace Database\Factories;

use App\Models\AvailabilityBlock;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;

class AvailabilityBlockFactory extends Factory
{
    protected $model = AvailabilityBlock::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('now', '+30 days')->format('Y-m-d');
        $end = $this->faker->dateTimeBetween('+31 days', '+60 days')->format('Y-m-d');

        return [
            'listing_id' => Listing::factory(),
            'start_date' => $start,
            'end_date'   => $end,
            'reason'     => 'maintenance',
        ];
    }
}
