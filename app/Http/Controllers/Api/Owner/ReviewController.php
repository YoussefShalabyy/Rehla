<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Owner;

use App\Http\Controllers\Controller;
use App\Http\Requests\Review\ReplyReviewRequest;
use App\Http\Resources\Review\ReviewResource;
use App\Models\Review;
use App\Services\Listing\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService) {}

    /**
     * Reply to a review on owner's listing.
     */
    public function reply(ReplyReviewRequest $request, string $uuid): JsonResponse
    {
        $review = Review::where('uuid', $uuid)->firstOrFail();
        
        Gate::authorize('reply', $review);

        $this->reviewService->ownerReply($review, $request->input('reply'), $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Reply submitted successfully.',
            'data'    => new ReviewResource($review->load('reviewer')),
        ]);
    }
}
