<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

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

    public function block(Request $request, string $uuid): JsonResponse
    {
        $listing = Listing::where('uuid', $uuid)->firstOrFail();

        $validated = $request->validate([
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date'   => ['required', 'date', 'after_or_equal:start_date'],
            'reason'     => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $block = $this->availabilityService->blockDates(
                $listing,
                $request->user(),
                $validated['start_date'],
                $validated['end_date'],
                $validated['reason'] ?? null
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

    public function unblock(string $uuid, int $id): JsonResponse
    {
        $listing = Listing::where('uuid', $uuid)->firstOrFail();
        $block   = AvailabilityBlock::where('id', $id)
            ->where('listing_id', $listing->id)
            ->firstOrFail();

        $this->availabilityService->unblockDates($block);

        return $this->success(null, 'Dates unblocked successfully.');
    }
}
