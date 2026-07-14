<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\DTOs\Listing\CreateListingDTO;
use App\DTOs\Listing\UpdateListingDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Listing\CreateListingRequest;
use App\Http\Requests\Listing\UpdateListingRequest;
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
        $status    = $request->query('status');
        $search    = $request->query('search');
        $paginator = $this->listingService->getAllForAdmin($status, $search);

        return $this->paginated($paginator, ListingListResource::class);
    }

    public function show(string $uuid): JsonResponse
    {
        $listing = $this->listingService->findByUuid($uuid);

        return $this->success(new ListingResource($listing));
    }

    public function store(CreateListingRequest $request): JsonResponse
    {
        $dto     = CreateListingDTO::fromRequest($request);
        $listing = $this->listingService->create($dto, $request->user());

        return $this->created(new ListingResource($listing), 'Listing created successfully.');
    }

    public function update(UpdateListingRequest $request, string $uuid): JsonResponse
    {
        $listing = $this->listingService->findByUuid($uuid);
        $dto     = UpdateListingDTO::fromRequest($request);
        $listing = $this->listingService->update($listing, $dto);

        return $this->success(new ListingResource($listing), 'Listing updated successfully.');
    }

    public function destroy(string $uuid): JsonResponse
    {
        $listing = $this->listingService->findByUuid($uuid);
        $this->listingService->delete($listing);

        return $this->success(null, 'Listing deleted successfully.');
    }

    public function updateStatus(Request $request, string $uuid): JsonResponse
    {
        $request->validate(['status' => 'required|string|in:active,hidden,disabled,archived']);

        $listing = $this->listingService->findByUuid($uuid);
        $listing = $this->listingService->updateStatus($listing, \App\Enums\ListingStatus::from($request->input('status')));

        return $this->success(new ListingResource($listing), 'Listing status updated successfully.');
    }
}
