<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Review\ReviewResource;
use App\Models\Review;
use App\Services\Listing\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService) {}

    /**
     * Get all reviews (filterable by status)
     */
    public function index(Request $request): JsonResponse
    {
        $query = Review::with(['reviewer', 'listing']);

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $reviews = $query->latest()->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data'    => ReviewResource::collection($reviews),
            'meta'    => [
                'pagination' => [
                    'current_page' => $reviews->currentPage(),
                    'last_page'    => $reviews->lastPage(),
                    'per_page'     => $reviews->perPage(),
                    'total'        => $reviews->total(),
                ],
            ],
        ]);
    }

    /**
     * Moderate a review
     */
    public function moderate(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'status' => ['required', 'string', 'in:pending,approved,hidden'],
        ]);

        $review = Review::where('uuid', $uuid)->firstOrFail();
        $status = ReviewStatus::from($request->input('status'));

        $this->reviewService->moderate($review, $status, $request->user());

        return response()->json([
            'success' => true,
            'message' => "Review marked as {$status->value}.",
            'data'    => new ReviewResource($review->load('reviewer')),
        ]);
    }
}
