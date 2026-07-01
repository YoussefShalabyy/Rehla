<?php

declare(strict_types=1);

namespace App\Enums;

enum MediaProvider: string
{
    case Cloudinary = 'cloudinary';
    case S3         = 's3';
    case R2         = 'r2';
    case Local      = 'local';
}
