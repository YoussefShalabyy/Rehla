<?php

declare(strict_types=1);

namespace App\Services\Media\Adapters;

use App\Interfaces\MediaStorageInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * LocalMediaAdapter — used in tests and local development.
 * Stores files in storage/app/public/media/.
 * Never calls Cloudinary or any external provider.
 */
final class LocalMediaAdapter implements MediaStorageInterface
{
    public function upload(UploadedFile $file, string $folder): array
    {
        $publicId = $folder . '/' . Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path     = $file->storeAs('media', $publicId, 'public');

        return [
            'url'       => Storage::disk('public')->url($path),
            'public_id' => $publicId,
        ];
    }

    public function delete(string $publicId): void
    {
        Storage::disk('public')->delete('media/' . $publicId);
    }
}
