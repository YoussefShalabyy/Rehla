<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes — VistaStay
|--------------------------------------------------------------------------
| All routes here are prefixed with /api/v1/ automatically (set in bootstrap/app.php).
| Group routes by domain and authentication requirement.
|
| Route groups:
|   Public      — no auth required
|   Auth guard  — require auth:sanctum middleware
|   Admin       — require auth:sanctum + admin role middleware
|   Owner       — require auth:sanctum + provider role middleware
*/

// ── Health Check ─────────────────────────────────────────────────────────────
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'VistaStay API is running.',
        'data' => ['version' => 'v1']
    ]);
});

// Public Auth Routes
Route::middleware('throttle:auth')->prefix('auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\Auth\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\Api\Auth\AuthController::class, 'login']);
    Route::post('/google', [\App\Http\Controllers\Api\Auth\AuthController::class, 'google']);
    Route::post('/apple', [\App\Http\Controllers\Api\Auth\AuthController::class, 'apple']);
});

// Webhook Routes (No Auth required)
Route::middleware('throttle:webhook')->prefix('webhooks')->group(function () {
    Route::post('/paymob', [\App\Http\Controllers\Api\Webhook\PaymobWebhookController::class, 'handle']);
});

// Public Listing & Booking Routes
Route::prefix('listings')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\Customer\ListingController::class, 'index']);
    Route::get('/{uuid}', [\App\Http\Controllers\Api\Customer\ListingController::class, 'show']);
    Route::get('/{uuid}/availability', [\App\Http\Controllers\Api\Customer\BookingController::class, 'availability']);
    Route::get('/{uuid}/reviews', [\App\Http\Controllers\Api\Customer\ReviewController::class, 'index']);
});

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    Route::prefix('auth')->group(function () {
        Route::get('/me', [\App\Http\Controllers\Api\Auth\AuthController::class, 'me']);
        Route::post('/logout', [\App\Http\Controllers\Api\Auth\AuthController::class, 'logout']);
    });

    // Owner Endpoints
    Route::middleware('role:provider')->prefix('owner')->group(function () {
        Route::get('/dashboard/stats', [\App\Http\Controllers\Api\Owner\DashboardController::class, 'stats']);
        
        Route::get('/listings', [\App\Http\Controllers\Api\Owner\ListingController::class, 'index']);
        Route::post('/listings', [\App\Http\Controllers\Api\Owner\ListingController::class, 'store']);
        Route::put('/listings/{uuid}', [\App\Http\Controllers\Api\Owner\ListingController::class, 'update']);
        Route::delete('/listings/{uuid}', [\App\Http\Controllers\Api\Owner\ListingController::class, 'destroy']);
        
        // Media Management for Listings
        Route::post('/listings/{uuid}/media', [\App\Http\Controllers\Api\Owner\MediaController::class, 'upload']);
        Route::put('/listings/{uuid}/media/reorder', [\App\Http\Controllers\Api\Owner\MediaController::class, 'reorder']);

        // Global Media Management (Delete / Set Primary)
        Route::delete('/media/{uuid}', [\App\Http\Controllers\Api\Owner\MediaController::class, 'destroy']);
        Route::put('/media/{uuid}/primary', [\App\Http\Controllers\Api\Owner\MediaController::class, 'setPrimary']);
        
        // Owner Review Management
        Route::get('/reviews', [\App\Http\Controllers\Api\Owner\ReviewController::class, 'index']); // Optional, wait roadmap didn't define index for reviews in owner earlier, it defined it in phase 10. Let's create it later if needed. The roadmap says GET /api/v1/owner/reviews -> we can just reuse ListingController or Bookings. I will leave it to the generic ones.
        Route::post('/reviews/{uuid}/reply', [\App\Http\Controllers\Api\Owner\ReviewController::class, 'reply']);
    });

    Route::middleware('role:provider')->prefix('owner/bookings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Owner\BookingController::class, 'index']);
    });

    Route::middleware('role:provider')->prefix('owner/availability')->group(function () {
        Route::post('/block', [\App\Http\Controllers\Api\Owner\AvailabilityController::class, 'block']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\Owner\AvailabilityController::class, 'unblock']);
    });

    // Admin Routes
    Route::middleware('role:admin')->group(function () {
        Route::prefix('admin/dashboard')->group(function () {
            Route::get('/stats', [\App\Http\Controllers\Api\Admin\DashboardController::class, 'stats']);
        });

        Route::prefix('admin/users')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\UserController::class, 'index']);
            Route::put('/{uuid}/status', [\App\Http\Controllers\Api\Admin\UserController::class, 'updateStatus']);
        });

        Route::prefix('admin/settings')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\SettingsController::class, 'index']);
            Route::put('/{key}', [\App\Http\Controllers\Api\Admin\SettingsController::class, 'update']);
        });

        Route::prefix('admin/listings')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\ListingController::class, 'index']);
            Route::post('/{uuid}/approve', [\App\Http\Controllers\Api\Admin\ListingController::class, 'approve']);
            Route::post('/{uuid}/reject', [\App\Http\Controllers\Api\Admin\ListingController::class, 'reject']);
        });

        Route::prefix('admin/bookings')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\BookingController::class, 'index']);
            Route::put('/{uuid}/status', [\App\Http\Controllers\Api\Admin\BookingController::class, 'updateStatus']);
        });

        Route::prefix('admin/reviews')->group(function () {
            Route::get('/', [\App\Http\Controllers\Api\Admin\ReviewController::class, 'index']);
            Route::put('/{uuid}/moderate', [\App\Http\Controllers\Api\Admin\ReviewController::class, 'moderate']);
        });
    });

    // Customer Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Customer\NotificationController::class, 'index']);
        Route::get('/unread', [\App\Http\Controllers\Api\Customer\NotificationController::class, 'unreadCount']);
        Route::put('/read-all', [\App\Http\Controllers\Api\Customer\NotificationController::class, 'markAllAsRead']);
        Route::put('/{id}/read', [\App\Http\Controllers\Api\Customer\NotificationController::class, 'markAsRead']);
    });

    // Customer Booking Routes
    Route::prefix('bookings')->group(function () {
        // Bookings
        Route::get('/', [\App\Http\Controllers\Api\Customer\BookingController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Customer\BookingController::class, 'store']);
        Route::get('/{uuid}', [\App\Http\Controllers\Api\Customer\BookingController::class, 'show']);
        Route::post('/{uuid}/cancel', [\App\Http\Controllers\Api\Customer\BookingController::class, 'cancel']);
    });

    // Customer Payment Routes
    Route::middleware('throttle:payment')->prefix('payments')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\Customer\PaymentController::class, 'initiate']);
        Route::get('/history', [\App\Http\Controllers\Api\Customer\PaymentController::class, 'history']);
        Route::get('/{uuid}', [\App\Http\Controllers\Api\Customer\PaymentController::class, 'show']);
    });

    // Customer Review Routes
    Route::prefix('reviews')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\Customer\ReviewController::class, 'store']);
    });
    
});
