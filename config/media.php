<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Default Media Storage Provider
    |--------------------------------------------------------------------------
    | Supported: "local", "cloudinary", "s3", "r2"
    | Set to "local" for local development and testing.
    */
    'default' => env('MEDIA_PROVIDER', 'local'),

    'cloudinary' => [
        'cloud_name' => env('CLOUDINARY_CLOUD_NAME', ''),
        'api_key'    => env('CLOUDINARY_API_KEY', ''),
        'api_secret' => env('CLOUDINARY_API_SECRET', ''),
        'secure'     => true,
    ],

    's3' => [
        'bucket' => env('AWS_BUCKET', ''),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'url'    => env('AWS_URL', ''),
    ],

    'max_file_size_kb' => env('MEDIA_MAX_FILE_SIZE_KB', 10240), // 10 MB default

];
