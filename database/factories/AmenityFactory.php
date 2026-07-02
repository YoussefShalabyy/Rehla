<?php

namespace Database\Factories;

use App\Models\Amenity;
use Illuminate\Database\Eloquent\Factories\Factory;

class AmenityFactory extends Factory
{
    protected $model = Amenity::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->word(),
            'icon' => 'fa-' . $this->faker->word(),
        ];
    }
}
