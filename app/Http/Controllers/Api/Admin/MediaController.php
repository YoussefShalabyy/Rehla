<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\DTOs\Media\UploadMediaDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\Media\MediaResource;
use App\Models\Listing;
use App\Models\Media;
use App\Services\Media\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MediaController extends Controller
{
    public function __construct(private readonly MediaService $mediaService)
    {
    }

    public function upload(Request $request, string $uuid): JsonResponse
    {
        $listing = Listing::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'file' => ['required', 'image', 'max:' . config('media.max_file_size_kb')],
        ]);

        $dto   = new UploadMediaDTO(
            entityType: 'listing',
            entityId: $listing->id,
            folder: "listings/{$listing->uuid}",
            file: $request->file('file')
        );
        $media = $this->mediaService->uploadMedia($dto);

        return response()->json([
            'success' => true,
            'message' => 'Media uploaded successfully.',
            'data'    => new MediaResource($media),
        ], 201);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $media = Media::where('uuid', $uuid)->firstOrFail();
        $this->mediaService->deleteMedia($media);

        return $this->success(null, 'Media deleted successfully.');
    }

    public function setPrimary(string $uuid): JsonResponse
    {
        $media = Media::where('uuid', $uuid)->firstOrFail();
        $this->mediaService->setPrimary($media);

        return $this->success(new MediaResource($media->fresh()), 'Primary media updated successfully.');
    }

    public function reorder(Request $request, string $uuid): JsonResponse
    {
        $listing = Listing::where('uuid', $uuid)->firstOrFail();

        $request->validate([
            'uuids'   => ['required', 'array'],
            'uuids.*' => ['string', 'exists:media,uuid'],
        ]);

        $this->mediaService->reorderMedia('listing', $listing->id, $request->input('uuids'));

        return $this->success(null, 'Media reordered successfully.');
    }
}
