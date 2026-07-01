<?php

namespace Database\Factories;

use App\Models\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
            'owner_id' => \App\Models\User::factory(),
            'type' => \App\Enums\ListingType::Property,
            'property_type' => \App\Enums\PropertyType::Apartment,
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'address' => fake()->streetAddress(),
            'country' => 'Egypt',
            'city' => 'Cairo',
            'latitude' => fake()->latitude(),
            'longitude' => fake()->longitude(),
            'base_price_cents' => fake()->numberBetween(10000, 100000), // 100 - 1000 EGP
            'cleaning_fee_cents' => 5000, // 50 EGP
            'extra_guest_fee_cents' => 2000, // 20 EGP
            'max_guests' => fake()->numberBetween(1, 10),
            'status' => \App\Enums\ListingStatus::Pending,
            'is_instant_bookable' => false,
        ];
    }
}
