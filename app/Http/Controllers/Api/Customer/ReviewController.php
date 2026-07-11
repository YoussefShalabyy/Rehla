<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Customer;

use App\DTOs\Review\CreateReviewDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Review\CreateReviewRequest;
use App\Http\Resources\Review\ReviewResource;
use App\Models\Booking;
use App\Models\Listing;
use App\Services\Listing\ReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService) {}

    /**
     * Get a pending completed booking that needs a review.
     */
    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();

        $booking = Booking::with('listing.media')
            ->where('customer_id', $user->id)
            ->where('status', \App\Enums\BookingStatus::Completed)
            ->whereDoesntHave('review')
            ->orderBy('check_out_date', 'desc')
            ->first();

        if (!$booking) {
            return response()->json([
                'success' => true,
                'data' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => new \App\Http\Resources\Booking\BookingResource($booking)
        ]);
    }

    /**
     * Create a new review for a completed booking.
     */
    public function store(CreateReviewRequest $request): JsonResponse
    {
        $booking = Booking::where('uuid', $request->input('booking_uuid'))->firstOrFail();
        
        Gate::authorize('create', [\App\Models\Review::class, $booking]);

        $dto = CreateReviewDTO::fromRequest($request);
        $review = $this->reviewService->createReview($dto, $request->user());

        return response()->json([
            'success' => true,
            'message' => 'Review submitted successfully.',
            'data'    => new ReviewResource($review->load('reviewer')),
        ], 201);
    }

    /**
     * Get paginated approved reviews for a listing.
     * This is a public endpoint (no auth required), but we place it in Customer namespace 
     * or it can be accessed without auth.
     */
    public function index(Request $request, string $listingUuid): JsonResponse
    {
        $listing = Listing::where('uuid', $listingUuid)->firstOrFail();
        $perPage = (int) $request->input('per_page', 20);

        $reviews = $this->reviewService->getListingReviews($listing, $perPage);
        $listing->refresh(); // ensure average_rating is fresh from DB

        return response()->json([
            'success' => true,
            'data'    => ReviewResource::collection($reviews),
            'meta'    => [
                'average_rating' => $listing->average_rating,
                'total_reviews'  => $reviews->total(),
                'pagination'     => [
                    'current_page' => $reviews->currentPage(),
                    'last_page'    => $reviews->lastPage(),
                    'per_page'     => $reviews->perPage(),
                    'total'        => $reviews->total(),
                ],
            ],
        ]);
    }
}
