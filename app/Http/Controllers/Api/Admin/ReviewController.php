<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ReviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\Review\ReviewResource;
use App\Models\Review;
use App\Models\Listing;
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

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('rating')) {
            $query->where('rating', $request->input('rating'));
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
     * Admin creates a custom review for a listing.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'listing_uuid'  => ['required', 'string', 'exists:listings,uuid'],
            'rating'        => ['required', 'integer', 'min:1', 'max:5'],
            'comment'       => ['nullable', 'string', 'max:1000'],
            'reviewer_name' => ['nullable', 'string', 'max:255'],
        ]);

        $listing = Listing::where('uuid', $validated['listing_uuid'])->firstOrFail();

        $review = $this->reviewService->createAdminReview(
            $listing,
            (int) $validated['rating'],
            $validated['comment'] ?? null,
            $validated['reviewer_name'] ?? null
        );

        return response()->json([
            'success' => true,
            'message' => 'Review added successfully.',
            'data'    => new ReviewResource($review->load('reviewer', 'listing')),
        ], 201);
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

    /**
     * Admin replies to a review.
     */
    public function reply(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'reply' => ['required', 'string', 'max:1000'],
        ]);

        $review = Review::where('uuid', $uuid)->firstOrFail();

        try {
            $review = $this->reviewService->adminReply($review, $request->input('reply'), $request->user());
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getStatusCode());
        }

        return response()->json([
            'success' => true,
            'message' => 'Reply added successfully.',
            'data'    => new ReviewResource($review->load('reviewer')),
        ]);
    }
}
