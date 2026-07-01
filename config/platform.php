<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Platform Default Settings
    |--------------------------------------------------------------------------
    | These are fallback values used when a setting is not found in
    | the platform_settings database table.
    | Always prefer DB values over these — they are fallbacks only.
    */

    'fee_percentage'          => env('PLATFORM_FEE_PERCENTAGE', 10),
    'cancellation_window_days' => env('CANCELLATION_WINDOW_DAYS', 7),
    'max_photos_per_listing'  => env('MAX_PHOTOS_PER_LISTING', 20),
    'max_guests_default'      => env('MAX_GUESTS_DEFAULT', 10),
    'booking_reference_prefix' => 'VS',
    'default_currency'        => 'EGP',

];
