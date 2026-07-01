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
Route::get('/health', fn () => response()->json([
    'success' => true,
    'message' => 'VistaStay API is running.',
    'data'    => ['version' => 'v1'],
    'meta'    => null,
    'errors'  => null,
]));
