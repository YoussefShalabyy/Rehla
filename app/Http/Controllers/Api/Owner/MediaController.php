<?php

namespace App\Http\Controllers\Api\Owner;

use App\DTOs\Media\UploadMediaDTO;
use App\Http\Controllers\Controller;
use App\Http\Resources\Media\MediaResource;
use App\Models\Listing;
use App\Models\Media;
use App\Services\Media\MediaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class MediaController extends Controller
{
    public function __construct(private MediaService $mediaService) {}

    public function upload(Request $request, string $uuid): JsonResponse
    {
        $listing = Listing::where('uuid', $uuid)->firstOrFail();
        
        Gate::authorize('upload', [Media::class, $listing]);

        $request->validate([
            'file' => ['required', 'image', 'max:' . config('media.max_file_size_kb')],
        ]);

        $dto = new UploadMediaDTO(
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
        
        Gate::authorize('delete', $media);

        $this->mediaService->deleteMedia($media);

        return response()->json([
            'success' => true,
            'message' => 'Media deleted successfully.',
        ]);
    }

    public function setPrimary(string $uuid): JsonResponse
    {
        $media = Media::where('uuid', $uuid)->firstOrFail();
        
        Gate::authorize('setPrimary', $media);

        $this->mediaService->setPrimary($media);

        return response()->json([
            'success' => true,
            'message' => 'Primary media updated successfully.',
            'data'    => new MediaResource($media->fresh()),
        ]);
    }

    public function reorder(Request $request, string $uuid): JsonResponse
    {
        $listing = Listing::where('uuid', $uuid)->firstOrFail();
        
        Gate::authorize('reorder', [Media::class, $listing]);

        $request->validate([
            'uuids'   => ['required', 'array'],
            'uuids.*' => ['string', 'exists:media,uuid'],
        ]);

        $this->mediaService->reorderMedia('listing', $listing->id, $request->input('uuids'));

        return response()->json([
            'success' => true,
            'message' => 'Media reordered successfully.',
        ]);
    }
}
