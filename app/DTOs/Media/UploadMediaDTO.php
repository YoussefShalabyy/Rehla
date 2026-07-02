<?php

declare(strict_types=1);

namespace App\DTOs\Media;

use Illuminate\Http\UploadedFile;

readonly class UploadMediaDTO
{
    public function __construct(
        public string $entityType,
        public int $entityId,
        public string $folder,
        public UploadedFile $file,
    ) {}
}
