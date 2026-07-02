<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Forbidden (no user)'], 403);
        }

        $userRoleValue = $user->role instanceof \UnitEnum ? $user->role->value : $user->role;

        if ($userRoleValue !== $role) {
            return response()->json(['message' => 'Forbidden (role mismatch: ' . $userRoleValue . ' !== ' . $role . ')'], 403);
        }

        return $next($request);
    }
}
