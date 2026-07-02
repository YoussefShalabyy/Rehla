<?php

declare(strict_types=1);

namespace App\Services\Media\Adapters;

use App\Interfaces\MediaStorageInterface;
use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class CloudinaryAdapter implements MediaStorageInterface
{
    private Cloudinary $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary([
            'cloud' => [
                'cloud_name' => config('media.cloudinary.cloud_name'),
                'api_key'    => config('media.cloudinary.api_key'),
                'api_secret' => config('media.cloudinary.api_secret'),
            ],
            'url' => [
                'secure' => true,
            ]
        ]);
    }

    /**
     * Upload a file to Cloudinary.
     *
     * @return array{url: string, public_id: string}
     */
    public function upload(UploadedFile $file, string $folder): array
    {
        try {
            $response = $this->cloudinary->uploadApi()->upload(
                $file->getRealPath(),
                ['folder' => $folder]
            );

            return [
                'url'       => $response['secure_url'],
                'public_id' => $response['public_id'],
            ];
        } catch (\Exception $e) {
            Log::error('Cloudinary Upload Failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to upload media to Cloudinary.', 0, $e);
        }
    }

    /**
     * Delete a file from Cloudinary using its public ID.
     */
    public function delete(string $publicId): void
    {
        try {
            $this->cloudinary->uploadApi()->destroy($publicId);
        } catch (\Exception $e) {
            Log::error('Cloudinary Delete Failed: ' . $e->getMessage());
            // We might choose to swallow this or throw it depending on if we want 
            // the DB deletion to fail if Cloudinary fails to delete (e.g., if it was already deleted).
            // Usually, we want to log and move on.
        }
    }
}
