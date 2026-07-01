<?php

declare(strict_types=1);

namespace App\Interfaces;

use Illuminate\Http\UploadedFile;

interface MediaStorageInterface
{
    /**
     * Upload a file to the provider.
     *
     * @return array{url: string, public_id: string}
     */
    public function upload(UploadedFile $file, string $folder): array;

    /**
     * Delete a file from the provider using its public ID.
     */
    public function delete(string $publicId): void;
}
