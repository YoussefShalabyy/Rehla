<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            [
                'key'   => 'platform_fee_percentage',
                'value' => '10',
                'type'  => 'integer',
                'description' => 'Platform fee percentage applied to bookings',
            ],
            [
                'key'   => 'cancellation_window_days',
                'value' => '7',
                'type'  => 'integer',
                'description' => 'Number of days before check-in where cancellation is free',
            ],
            [
                'key'   => 'max_photos_per_listing',
                'value' => '20',
                'type'  => 'integer',
                'description' => 'Maximum photos allowed per listing',
            ],
            [
                'key'   => 'max_guests_default',
                'value' => '10',
                'type'  => 'integer',
                'description' => 'Default maximum guests allowed per listing',
            ],
        ];

        foreach ($settings as $setting) {
            DB::table('platform_settings')->updateOrInsert(
                ['key' => $setting['key']],
                [
                    'value'       => $setting['value'],
                    'type'        => $setting['type'],
                    'description' => $setting['description'],
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]
            );
        }
    }
}
