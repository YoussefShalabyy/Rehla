<?php

declare(strict_types=1);

namespace App\Services\Media;

use App\DTOs\Media\UploadMediaDTO;
use App\Interfaces\MediaStorageInterface;
use App\Models\Media;
use App\Models\PlatformSetting;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\HttpException;

class MediaService
{
    public function __construct(private MediaStorageInterface $storage) {}

    /**
     * Upload an image and attach it to the specified entity (e.g., Listing, User).
     */
    public function uploadMedia(UploadMediaDTO $dto): Media
    {
        if ($dto->entityType === 'listing') {
            $this->ensureListingMaxPhotosNotExceeded($dto->entityId);
        }

        // Upload to Cloudinary (or local storage)
        $result = $this->storage->upload($dto->file, $dto->folder);

        return DB::transaction(function () use ($dto, $result) {
            // Determine if this should be the primary image.
            // If it's a listing and it has no images yet, make it primary.
            $isPrimary = false;
            if ($dto->entityType === 'listing') {
                $existingCount = Media::where('entity_type', 'listing')
                    ->where('entity_id', $dto->entityId)
                    ->count();
                $isPrimary = ($existingCount === 0);
            }

            // Get the highest order number
            $maxOrder = Media::where('entity_type', $dto->entityType)
                ->where('entity_id', $dto->entityId)
                ->max('order') ?? 0;

            return Media::create([
                'entity_type' => $dto->entityType,
                'entity_id'   => $dto->entityId,
                'url'         => $result['url'],
                'public_id'   => $result['public_id'],
                'type'        => 'image',
                'provider'    => config('media.default', 'local'),
                'is_primary'  => $isPrimary,
                'order'       => $maxOrder + 1,
            ]);
        });
    }

    /**
     * Delete a media item.
     * Deletes from provider FIRST, then from DB.
     */
    public function deleteMedia(Media $media): void
    {
        // 1. Delete from provider
        $this->storage->delete($media->public_id);

        // 2. Delete from DB
        $media->delete();
    }

    /**
     * Set a media item as primary. Clears the previous primary in the same entity.
     */
    public function setPrimary(Media $media): void
    {
        DB::transaction(function () use ($media) {
            // Clear existing primary
            Media::where('entity_type', $media->entity_type)
                ->where('entity_id', $media->entity_id)
                ->update(['is_primary' => false]);

            // Set new primary
            $media->update(['is_primary' => true]);
        });
    }

    /**
     * Reorder media for a specific entity.
     */
    public function reorderMedia(string $entityType, int $entityId, array $orderedUuids): void
    {
        DB::transaction(function () use ($entityType, $entityId, $orderedUuids) {
            foreach ($orderedUuids as $index => $uuid) {
                Media::where('entity_type', $entityType)
                    ->where('entity_id', $entityId)
                    ->where('uuid', $uuid)
                    ->update(['order' => $index + 1]);
            }
        });
    }

    private function ensureListingMaxPhotosNotExceeded(int $listingId): void
    {
        $maxPhotos = (int) PlatformSetting::get('max_photos_per_listing', 20);

        $currentCount = Media::where('entity_type', 'listing')
            ->where('entity_id', $listingId)
            ->count();

        if ($currentCount >= $maxPhotos) {
            throw new HttpException(422, "You cannot upload more than {$maxPhotos} photos for this listing.");
        }
    }
}
