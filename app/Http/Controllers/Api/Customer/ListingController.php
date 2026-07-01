<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Customer;

use App\DTOs\Listing\SearchListingDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Listing\SearchListingRequest;
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

    public function index(SearchListingRequest $request): JsonResponse
    {
        $dto = SearchListingDTO::fromRequest($request);
        $paginator = $this->listingService->search($dto);

        return $this->paginated($paginator, ListingListResource::class);
    }

    public function show(string $uuid): JsonResponse
    {
        $listing = $this->listingService->findByUuid($uuid);

        if ($listing->status !== \App\Enums\ListingStatus::Published) {
            // For MVP, if it's not published, customers can't see it
            // Could throw a NotFoundException, but let's just abort
            abort(404, 'Listing not found.');
        }

        return $this->success(new ListingResource($listing), 'Listing retrieved successfully.');
    }
}
