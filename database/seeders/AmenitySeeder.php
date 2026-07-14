<?php

namespace Database\Seeders;

use App\Models\Amenity;
use Illuminate\Database\Seeder;

class AmenitySeeder extends Seeder
{
    public function run(): void
    {
        \Illuminate\Support\Facades\DB::table('listing_amenity')->delete();
        \Illuminate\Support\Facades\DB::table('amenities')->delete();

        $amenities = [
            // Property Amenities
            ['name' => 'WiFi', 'icon' => 'fa-wifi', 'type' => 'property'],
            ['name' => 'Pool', 'icon' => 'fa-swimming-pool', 'type' => 'property'],
            ['name' => 'Parking', 'icon' => 'fa-parking', 'type' => 'property'],
            ['name' => 'Air Conditioning', 'icon' => 'fa-snowflake', 'type' => 'property'],
            ['name' => 'TV', 'icon' => 'fa-tv', 'type' => 'property'],
            ['name' => 'Kitchen', 'icon' => 'fa-utensils', 'type' => 'property'],
            ['name' => 'Washer', 'icon' => 'fa-soap', 'type' => 'property'],
            
            // Car Amenities
            ['name' => 'Automatic Transmission', 'icon' => 'fa-car', 'type' => 'car'],
            ['name' => 'Manual Transmission', 'icon' => 'fa-cogs', 'type' => 'car'],
            ['name' => 'Leather Seats', 'icon' => 'fa-chair', 'type' => 'car'],
            ['name' => 'Sunroof / Panoramic', 'icon' => 'fa-sun', 'type' => 'car'],
            ['name' => 'Apple CarPlay / Android Auto', 'icon' => 'fa-mobile-alt', 'type' => 'car'],
            ['name' => 'Bluetooth', 'icon' => 'fa-bluetooth', 'type' => 'car'],
            ['name' => 'GPS Navigation', 'icon' => 'fa-location-arrow', 'type' => 'car'],
            ['name' => 'Rear Camera', 'icon' => 'fa-camera', 'type' => 'car'],
            ['name' => 'Parking Sensors', 'icon' => 'fa-wave-square', 'type' => 'car'],
            ['name' => 'Cruise Control', 'icon' => 'fa-tachometer-alt', 'type' => 'car'],
        ];

        foreach ($amenities as $amenity) {
            Amenity::create([
                'name' => $amenity['name'],
                'icon' => $amenity['icon'],
                'type' => $amenity['type'],
            ]);
        }
    }
}
