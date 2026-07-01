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

// Public Listing Routes
Route::prefix('listings')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\Customer\ListingController::class, 'index']);
    Route::get('/{uuid}', [\App\Http\Controllers\Api\Customer\ListingController::class, 'show']);
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

    // Admin Routes
    Route::prefix('admin/listings')->group(function () {
        Route::get('/', [\App\Http\Controllers\Api\Admin\ListingController::class, 'index']);
        Route::post('/{uuid}/approve', [\App\Http\Controllers\Api\Admin\ListingController::class, 'approve']);
        Route::post('/{uuid}/reject', [\App\Http\Controllers\Api\Admin\ListingController::class, 'reject']);
    });
    
});
