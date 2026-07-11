<?php

namespace Database\Factories;

use App\Models\Listing;
use App\Models\User;
use App\Enums\UserRole;
use App\Enums\ListingType;
use App\Enums\ListingStatus;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ListingFactory extends Factory
{
    protected $model = Listing::class;

    public function definition(): array
    {
        return [
            'uuid'          => (string) Str::uuid(),
            'created_by'    => User::factory()->create(['role' => UserRole::Admin])->id,
            'title'         => $this->faker->sentence(3),
            'description'   => $this->faker->paragraph(),
            'type'          => $this->faker->randomElement([ListingType::Property, ListingType::Car]),
            'property_type' => fn(array $attributes) => $attributes['type'] === ListingType::Property
                ? $this->faker->randomElement(['apartment', 'hotel', 'villa', 'room'])
                : null,
            'address'       => $this->faker->streetAddress(),
            'country'       => 'Egypt',
            'city'          => 'Cairo',
            'latitude'      => $this->faker->latitude(),
            'longitude'     => $this->faker->longitude(),
            'base_price_cents' => $this->faker->numberBetween(10000, 100000),
            'cleaning_fee_cents' => 5000,
            'extra_guest_fee_cents' => 2000,
            'max_guests'    => $this->faker->numberBetween(1, 10),
            'status'        => ListingStatus::Published,
            'is_instant_bookable' => true,
        ];
    }

    public function property(): static
    {
        return $this->state([
            'type'          => ListingType::Property,
            'property_type' => 'apartment',
        ]);
    }

    public function car(): static
    {
        return $this->state([
            'type'          => ListingType::Car,
            'property_type' => null,
        ]);
    }

    public function published(): static
    {
        return $this->state(['status' => ListingStatus::Published]);
    }

    public function pending(): static
    {
        return $this->state(['status' => ListingStatus::Pending]);
    }

    public function rejected(): static
    {
        return $this->state(['status' => ListingStatus::Rejected]);
    }
}
