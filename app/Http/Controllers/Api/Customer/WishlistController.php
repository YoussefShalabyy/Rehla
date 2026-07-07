<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Models\Listing;
use App\Models\Wishlist;
use App\Http\Resources\Listing\ListingListResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WishlistController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $wishlists = Wishlist::with(['listing.primaryMedia', 'listing.owner', 'listing.amenities'])
            ->where('user_id', $user->id)
            ->get()
            ->pluck('listing');
            
        return $this->success(
            ListingListResource::collection($wishlists),
            'Wishlists retrieved successfully.'
        );
    }

    public function toggle(Request $request, string $listingUuid): JsonResponse
    {
        $user = $request->user();
        $listing = Listing::where('uuid', $listingUuid)->firstOrFail();

        $wishlist = Wishlist::where('user_id', $user->id)
            ->where('listing_id', $listing->id)
            ->first();

        if ($wishlist) {
            $wishlist->delete();
            return $this->success(null, 'Removed from wishlist.');
        }

        Wishlist::create([
            'user_id' => $user->id,
            'listing_id' => $listing->id,
        ]);

        return $this->success(null, 'Added to wishlist.', 201);
    }
}
