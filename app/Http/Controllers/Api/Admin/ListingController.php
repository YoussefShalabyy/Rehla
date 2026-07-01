<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Listing\ListingListResource;
use App\Http\Resources\Listing\ListingResource;
use App\Services\Listing\ListingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ListingController extends Controller
{
    public function __construct(private readonly ListingService $listingService)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Listing::class);
        // We enforce admin access via routes middleware, but policy is good practice

        $status = $request->query('status');
        $paginator = $this->listingService->getAllForAdmin($status);

        return $this->paginated($paginator, ListingListResource::class);
    }

    public function approve(Request $request, string $uuid): JsonResponse
    {
        $listing = $this->listingService->findByUuid($uuid);
        
        $this->authorize('approve', $listing);

        $listing = $this->listingService->approve($listing, $request->user());

        return $this->success(new ListingResource($listing), 'Listing approved successfully.');
    }

    public function reject(Request $request, string $uuid): JsonResponse
    {
        $request->validate(['reason' => 'required|string']);

        $listing = $this->listingService->findByUuid($uuid);
        
        $this->authorize('reject', $listing);

        $listing = $this->listingService->reject($listing, $request->user(), $request->input('reason'));

        return $this->success(new ListingResource($listing), 'Listing rejected successfully.');
    }
}
