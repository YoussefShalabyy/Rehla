<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — Rehla
|--------------------------------------------------------------------------
| All routes here are prefixed with /api/v1/ automatically (set in bootstrap/app.php).
|
| Route groups:
|   Public  — no auth required
|   Auth    — require auth:sanctum middleware
|   Admin   — require auth:sanctum + admin role middleware (listings are admin-only)
*/

// ── Health Check ─────────────────────────────────────────────────────────────
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'VistaStay API is running.',
        'data'    => ['version' => 'v1'],
    ]);
});

// ── Public Auth Routes ────────────────────────────────────────────────────────
Route::middleware('throttle:auth')->prefix('auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\Auth\AuthController::class, 'register']);
    Route::post('/login',    [\App\Http\Controllers\Api\Auth\AuthController::class, 'login']);
    Route::post('/google',   [\App\Http\Controllers\Api\Auth\AuthController::class, 'google']);
    Route::post('/apple',    [\App\Http\Controllers\Api\Auth\AuthController::class, 'apple']);
});

// ── Webhook Routes (No Auth required) ────────────────────────────────────────
Route::middleware('throttle:webhook')->prefix('webhooks')->group(function () {
    Route::post('/paymob', [\App\Http\Controllers\Api\Webhook\PaymobWebhookController::class, 'handle']);
});

// ── Public Listing Routes ─────────────────────────────────────────────────────
Route::prefix('listings')->group(function () {
    Route::get('/',                    [\App\Http\Controllers\Api\Customer\ListingController::class, 'index']);
    Route::get('/{uuid}',              [\App\Http\Controllers\Api\Customer\ListingController::class, 'show']);
    Route::get('/{uuid}/availability', [\App\Http\Controllers\Api\Customer\BookingController::class, 'availability']);
    Route::get('/{uuid}/reviews',      [\App\Http\Controllers\Api\Customer\ReviewController::class, 'index']);
});

// ── Public Destination Routes ─────────────────────────────────────────────────
Route::prefix('destinations')->group(function () {
    Route::get('/suggested', [\App\Http\Controllers\Api\Customer\DestinationController::class, 'suggested']);
});

// ── Protected Routes (auth:sanctum) ──────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // ── Auth Management ───────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::get('/me',        [\App\Http\Controllers\Api\Auth\AuthController::class, 'me']);
        Route::put('/profile',   [\App\Http\Controllers\Api\Auth\AuthController::class, 'updateProfile']);
        Route::delete('/delete', [\App\Http\Controllers\Api\Auth\AuthController::class, 'deleteAccount']);
        Route::post('/logout',   [\App\Http\Controllers\Api\Auth\AuthController::class, 'logout']);
    });

    // ── Admin Routes ──────────────────────────────────────────────────────────
    Route::middleware('role:admin')->group(function () {

        // Admin Dashboard
        Route::prefix('admin/dashboard')->group(function () {
            Route::get('/stats', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'stats']);
        });

        // Admin Listings — full CRUD + approve/reject + media + availability
        Route::prefix('admin/listings')->group(function () {
            Route::get('/',                              [\App\Http\Controllers\Api\Admin\ListingController::class, 'index']);
            Route::post('/',                             [\App\Http\Controllers\Api\Admin\ListingController::class, 'store']);
            Route::get('/{uuid}',                        [\App\Http\Controllers\Api\Admin\ListingController::class, 'show']);
            Route::put('/{uuid}',                        [\App\Http\Controllers\Api\Admin\ListingController::class, 'update']);
            Route::delete('/{uuid}',                     [\App\Http\Controllers\Api\Admin\ListingController::class, 'destroy']);
            Route::put('/{uuid}/status',                 [\App\Http\Controllers\Api\Admin\ListingController::class, 'updateStatus']);
            // Media
            Route::post('/{uuid}/media',                 [\App\Http\Controllers\Api\Admin\MediaController::class, 'upload']);
            Route::put('/{uuid}/media/reorder',          [\App\Http\Controllers\Api\Admin\MediaController::class, 'reorder']);
            // Availability
            Route::post('/{uuid}/availability/block',    [\App\Http\Controllers\Api\Admin\AvailabilityController::class, 'block']);
            Route::delete('/{uuid}/availability/{id}',   [\App\Http\Controllers\Api\Admin\AvailabilityController::class, 'unblock']);
        });

        // Admin Media (global — delete / set primary by media uuid)
        Route::prefix('admin/media')->group(function () {
            Route::delete('/{uuid}',         [\App\Http\Controllers\Api\Admin\MediaController::class, 'destroy']);
            Route::put('/{uuid}/primary',    [\App\Http\Controllers\Api\Admin\MediaController::class, 'setPrimary']);
        });

        // Admin Users
        Route::prefix('admin/users')->group(function () {
            Route::get('/',              [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
            Route::get('/{uuid}',        [\App\Http\Controllers\Api\Admin\UserController::class, 'show']);
            Route::post('/',             [\App\Http\Controllers\Api\Admin\UserController::class, 'store']);
            Route::put('/{uuid}/status', [\App\Http\Controllers\Api\Admin\UserController::class, 'updateStatus']);
            Route::delete('/{uuid}',     [\App\Http\Controllers\Api\Admin\UserController::class, 'destroy']);
        });

        // Admin Bookings
        Route::prefix('admin/bookings')->group(function () {
            Route::get('/',               [\App\Http\Controllers\Api\Admin\BookingController::class, 'index']);
            Route::put('/{uuid}/status',  [\App\Http\Controllers\Api\Admin\BookingController::class, 'updateStatus']);
        });

        // Admin Reviews
        Route::prefix('admin/reviews')->group(function () {
            Route::get('/',               [\App\Http\Controllers\Api\Admin\ReviewController::class, 'index']);
            Route::post('/',              [\App\Http\Controllers\Api\Admin\ReviewController::class, 'store']);
            Route::put('/{uuid}/moderate',[\App\Http\Controllers\Api\Admin\ReviewController::class, 'moderate']);
            Route::post('/{uuid}/reply',  [\App\Http\Controllers\Api\Admin\ReviewController::class, 'reply']);
        });

        // Admin Settings
        Route::prefix('admin/settings')->group(function () {
            Route::get('/',      [\App\Http\Controllers\Api\Admin\SettingsController::class, 'index']);
            Route::put('/{key}', [\App\Http\Controllers\Api\Admin\SettingsController::class, 'update']);
        });

        // Admin Amenities
        Route::prefix('admin/amenities')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\AmenityController::class, 'index']);
        });

        // Admin Destinations
        Route::prefix('admin/destinations')->group(function () {
            Route::get('/',              [\App\Http\Controllers\Api\Admin\DestinationController::class, 'index']);
            Route::post('/',             [\App\Http\Controllers\Api\Admin\DestinationController::class, 'store']);
            Route::put('/{uuid}',        [\App\Http\Controllers\Api\Admin\DestinationController::class, 'update']);
            Route::delete('/{uuid}',     [\App\Http\Controllers\Api\Admin\DestinationController::class, 'destroy']);
        });
    });

    // ── Customer Notifications ────────────────────────────────────────────────
    Route::prefix('notifications')->group(function () {
        Route::get('/',          [\App\Http\Controllers\Api\Customer\NotificationController::class, 'index']);
        Route::get('/unread',    [\App\Http\Controllers\Api\Customer\NotificationController::class, 'unreadCount']);
        Route::put('/read-all',  [\App\Http\Controllers\Api\Customer\NotificationController::class, 'markAllAsRead']);
        Route::put('/{id}/read', [\App\Http\Controllers\Api\Customer\NotificationController::class, 'markAsRead']);
    });

    // ── Customer Bookings ─────────────────────────────────────────────────────
    Route::prefix('bookings')->group(function () {
        Route::get('/',               [\App\Http\Controllers\Api\Customer\BookingController::class, 'index']);
        Route::post('/',              [\App\Http\Controllers\Api\Customer\BookingController::class, 'store']);
        Route::get('/{uuid}',         [\App\Http\Controllers\Api\Customer\BookingController::class, 'show']);
        Route::post('/{uuid}/cancel', [\App\Http\Controllers\Api\Customer\BookingController::class, 'cancel']);
        Route::post('/{uuid}/reschedule', [\App\Http\Controllers\Api\Customer\BookingController::class, 'reschedule']);
    });

    // ── Customer Payments ─────────────────────────────────────────────────────
    Route::middleware('throttle:payment')->prefix('payments')->group(function () {
        Route::post('/',          [\App\Http\Controllers\Api\Customer\PaymentController::class, 'initiate']);
        Route::get('/history',    [\App\Http\Controllers\Api\Customer\PaymentController::class, 'history']);
        Route::get('/{uuid}',     [\App\Http\Controllers\Api\Customer\PaymentController::class, 'show']);
    });

    // ── Customer Reviews ──────────────────────────────────────────────────────
    Route::prefix('reviews')->group(function () {
        Route::get('/pending', [\App\Http\Controllers\Api\Customer\ReviewController::class, 'pending']);
        Route::post('/',       [\App\Http\Controllers\Api\Customer\ReviewController::class, 'store']);
    });

    // ── Customer Wishlist ─────────────────────────────────────────────────────
    Route::prefix('wishlists')->group(function () {
        Route::get('/',              [\App\Http\Controllers\Api\Customer\WishlistController::class, 'index']);
        Route::post('/{listingUuid}',[\App\Http\Controllers\Api\Customer\WishlistController::class, 'toggle']);
    });

    // ── Customer Wallet ───────────────────────────────────────────────────────
    Route::prefix('wallet')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Customer\WalletController::class, 'getWallet']);
    });
});
