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
Route::prefix('auth')->group(function () {
    Route::post('/register', [\App\Http\Controllers\Api\Auth\AuthController::class, 'register']);
    Route::post('/login', [\App\Http\Controllers\Api\Auth\AuthController::class, 'login']);
    Route::post('/google', [\App\Http\Controllers\Api\Auth\AuthController::class, 'google']);
    Route::post('/apple', [\App\Http\Controllers\Api\Auth\AuthController::class, 'apple']);
});

// Webhook Routes (No Auth required)
Route::prefix('webhooks')->group(function () {
    Route::post('/paymob', [\App\Http\Controllers\Api\Webhook\PaymobWebhookController::class, 'handle']);
});

// Public Listing & Booking Routes
Route::prefix('listings')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\Customer\ListingController::class, 'index']);
    Route::get('/{uuid}', [\App\Http\Controllers\Api\Customer\ListingController::class, 'show']);
    Route::get('/{uuid}/availability', [\App\Http\Controllers\Api\Customer\BookingController::class, 'availability']);
});

// Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    Route::prefix('auth')->group(function () {
        Route::get('/me', [\App\Http\Controllers\Api\Auth\AuthController::class, 'me']);
        Route::post('/logout', [\App\Http\Controllers\Api\Auth\AuthController::class, 'logout']);
    });

    // Owner Routes
    Route::prefix('owner/listings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Owner\ListingController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Owner\ListingController::class, 'store']);
        Route::put('/{uuid}', [\App\Http\Controllers\Api\Owner\ListingController::class, 'update']);
        Route::delete('/{uuid}', [\App\Http\Controllers\Api\Owner\ListingController::class, 'destroy']);
    });

    Route::prefix('owner/bookings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Owner\BookingController::class, 'index']);
    });

    Route::prefix('owner/availability')->group(function () {
        Route::post('/block', [\App\Http\Controllers\Api\Owner\AvailabilityController::class, 'block']);
        Route::delete('/{id}', [\App\Http\Controllers\Api\Owner\AvailabilityController::class, 'unblock']);
    });

    // Admin Routes
    Route::prefix('admin/listings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\ListingController::class, 'index']);
        Route::post('/{uuid}/approve', [\App\Http\Controllers\Api\Admin\ListingController::class, 'approve']);
        Route::post('/{uuid}/reject', [\App\Http\Controllers\Api\Admin\ListingController::class, 'reject']);
    });

    Route::prefix('admin/bookings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\BookingController::class, 'index']);
        Route::put('/{uuid}/status', [\App\Http\Controllers\Api\Admin\BookingController::class, 'updateStatus']);
    });

    // Customer Booking Routes
    Route::prefix('bookings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Customer\BookingController::class, 'index']);
        Route::post('/', [\App\Http\Controllers\Api\Customer\BookingController::class, 'store']);
        Route::get('/{uuid}', [\App\Http\Controllers\Api\Customer\BookingController::class, 'show']);
        Route::post('/{uuid}/cancel', [\App\Http\Controllers\Api\Customer\BookingController::class, 'cancel']);
    });

    // Customer Payment Routes
    Route::prefix('payments')->group(function () {
        Route::post('/', [\App\Http\Controllers\Api\Customer\PaymentController::class, 'initiate']);
        Route::get('/history', [\App\Http\Controllers\Api\Customer\PaymentController::class, 'history']);
        Route::get('/{uuid}', [\App\Http\Controllers\Api\Customer\PaymentController::class, 'show']);
    });
    
});
