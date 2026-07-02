<?php

namespace Database\Factories;

use App\Models\Media;
use App\Models\Listing;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MediaFactory extends Factory
{
    protected $model = Media::class;

    public function definition(): array
    {
        return [
            'uuid'       => (string) Str::uuid(),
            'listing_id' => Listing::factory(),
            'url'        => $this->faker->imageUrl(),
            'type'       => 'image',
            'is_primary' => false,
            'order'      => $this->faker->numberBetween(1, 10),
            'provider'   => 'cloudinary',
            'provider_id'=> $this->faker->uuid(),
        ];
    }
}
