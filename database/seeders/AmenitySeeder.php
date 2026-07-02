<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        $amenities = [
            ['name' => 'WiFi', 'icon' => 'fa-wifi'],
            ['name' => 'Pool', 'icon' => 'fa-swimming-pool'],
            ['name' => 'Parking', 'icon' => 'fa-parking'],
            ['name' => 'Air Conditioning', 'icon' => 'fa-snowflake'],
            ['name' => 'TV', 'icon' => 'fa-tv'],
            ['name' => 'Kitchen', 'icon' => 'fa-utensils'],
            ['name' => 'Washer', 'icon' => 'fa-soap'],
        ];

        foreach ($amenities as $amenity) {
            Amenity::firstOrCreate(
                ['name' => $amenity['name']],
                ['icon' => $amenity['icon']]
            );
        }
    }
}
