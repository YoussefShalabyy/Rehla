<?php

namespace Database\Seeders;

use App\Models\Destination;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DestinationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $destinations = [
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Sheikh Zayed',
                'subtitle'   => 'Giza Governorate, Egypt',
                'icon'       => 'business-outline',
                'icon_color' => '#003d9b',
                'icon_bg'    => 'rgba(0, 61, 155, 0.10)',
                'is_active'  => true,
                'sort_order' => 10,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Dubai',
                'subtitle'   => 'United Arab Emirates',
                'icon'       => 'sunny-outline',
                'icon_color' => '#b45309',
                'icon_bg'    => 'rgba(180, 83, 9, 0.10)',
                'is_active'  => true,
                'sort_order' => 20,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'Ain Sokhna',
                'subtitle'   => 'Suez Governorate, Egypt',
                'icon'       => 'water-outline',
                'icon_color' => '#0069a5',
                'icon_bg'    => 'rgba(0, 105, 165, 0.10)',
                'is_active'  => true,
                'sort_order' => 30,
            ],
            [
                'uuid'       => (string) Str::uuid(),
                'name'       => 'North Coast',
                'subtitle'   => 'Matrouh Governorate, Egypt',
                'icon'       => 'umbrella-outline',
                'icon_color' => '#1a7f4b',
                'icon_bg'    => 'rgba(26, 127, 75, 0.10)',
                'is_active'  => true,
                'sort_order' => 40,
            ],
        ];

        foreach ($destinations as $dest) {
            Destination::updateOrCreate(
                ['name' => $dest['name']], // Unique key for seed
                $dest
            );
        }
    }
}
