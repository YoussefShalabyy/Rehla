<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Push Notification Provider
    |--------------------------------------------------------------------------
    | Supported: "null", "expo", "firebase", "onesignal"
    | Set to "null" for local development and testing.
    */
    'default' => env('NOTIFICATION_PROVIDER', 'null'),

    'expo' => [
        'access_token' => env('EXPO_ACCESS_TOKEN', ''),
        'base_url'     => 'https://exp.host/--/api/v2/push/send',
    ],

    'firebase' => [
        'server_key' => env('FIREBASE_SERVER_KEY', ''),
    ],

];
