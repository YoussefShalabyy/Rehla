<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Models\AvailabilityBlock;
use App\Models\Listing;
use App\Services\Booking\AvailabilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    public function __construct(private readonly AvailabilityService $availabilityService)
    {
    }

    public function block(Request $request): JsonResponse
    {
        $request->validate([
            'listing_uuid' => ['required', 'exists:listings,uuid'],
            'start_date'   => ['required', 'date', 'after_or_equal:today'],
            'end_date'     => ['required', 'date', 'after_or_equal:start_date'],
            'reason'       => ['nullable', 'string'],
        ]);

        $listing = Listing::where('uuid', $request->listing_uuid)->firstOrFail();

        if ($request->user()->cannot('update', $listing)) {
            return $this->error('Unauthorized.', 403);
        }

        try {
            $block = $this->availabilityService->blockDates(
                $listing,
                $request->user(),
                $request->start_date,
                $request->end_date,
                $request->reason
            );

            return $this->created([
                'id'         => $block->id,
                'start_date' => $block->start_date->format('Y-m-d'),
                'end_date'   => $block->end_date->format('Y-m-d'),
                'reason'     => $block->reason,
            ], 'Dates blocked successfully.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 409);
        }
    }

    public function unblock(Request $request, int $id): JsonResponse
    {
        $block = AvailabilityBlock::findOrFail($id);
        
        $listing = Listing::findOrFail($block->listing_id);
        if ($request->user()->cannot('update', $listing)) {
            return $this->error('Unauthorized.', 403);
        }

        $this->availabilityService->unblockDates($block);

        return $this->success(null, 'Dates unblocked successfully.');
    }
}
