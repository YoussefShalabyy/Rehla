<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Return a successful JSON response.
     *
     * @param  mixed  $data
     */
    protected function success(
        mixed $data = null,
        string $message = 'Success',
        int $status = 200,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'meta'    => null,
            'errors'  => null,
        ], $status);
    }

    /**
     * Return a 201 Created JSON response.
     *
     * @param  mixed  $data
     */
    protected function created(mixed $data, string $message = 'Created successfully.'): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
            'meta'    => null,
            'errors'  => null,
        ], 201);
    }

    /**
     * Return an error JSON response.
     *
     * @param  array<string, mixed>  $errors
     */
    protected function error(
        string $message,
        int $status = 400,
        array $errors = [],
    ): JsonResponse {
        return response()->json([
            'success' => false,
            'message' => $message,
            'data'    => null,
            'meta'    => null,
            'errors'  => $errors ?: null,
        ], $status);
    }

    /**
     * Return a paginated JSON response with meta.
     *
     * @param  class-string  $resourceClass  Laravel API Resource class
     */
    protected function paginated(
        LengthAwarePaginator $paginator,
        string $resourceClass,
    ): JsonResponse {
        return response()->json([
            'success' => true,
            'message' => 'OK',
            'data'    => $resourceClass::collection($paginator),
            'meta'    => [
                'pagination' => [
                    'current_page' => $paginator->currentPage(),
                    'per_page'     => $paginator->perPage(),
                    'total'        => $paginator->total(),
                    'last_page'    => $paginator->lastPage(),
                    'count'        => $paginator->count(),
                    'total_pages'  => $paginator->lastPage(),
                ]
            ],
            'errors'  => null,
        ]);
    }
}
