<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\DTOs\Auth\LoginDTO;
use App\DTOs\Auth\RegisterDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\AuthUserResource;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $dto = RegisterDTO::fromRequest($request);
        
        $result = $this->authService->register($dto);

        return $this->created([
            'user' => new AuthUserResource($result['user']),
            'token' => $result['token'],
        ], 'User registered successfully.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $dto = LoginDTO::fromRequest($request);
        
        $result = $this->authService->login($dto);

        return $this->success([
            'user' => new AuthUserResource($result['user']),
            'token' => $result['token'],
        ], 'Logged in successfully.');
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return $this->success(null, 'Logged out successfully.');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(
            new AuthUserResource($request->user()),
            'User profile retrieved successfully.'
        );
    }
    
    // Social stubs
    public function google(Request $request): JsonResponse
    {
        $request->validate([
            'id_token' => 'required|string',
            'email' => 'required|email', // in reality extracted from token
            'name' => 'required|string', // in reality extracted from token
            'provider_id' => 'required|string', // in reality extracted from token
        ]);

        // MOCK validation: Normally we verify $request->id_token with Google
        $user = $this->authService->findOrCreateSocialUser(
            'google',
            $request->provider_id,
            $request->email,
            $request->name
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => new AuthUserResource($user),
            'token' => $token,
        ], 'Logged in with Google successfully.');
    }
    
    public function apple(Request $request): JsonResponse
    {
        $request->validate([
            'identity_token' => 'required|string',
            'email' => 'required|email', // in reality extracted from token
            'name' => 'required|string', // in reality extracted from token
            'provider_id' => 'required|string', // in reality extracted from token
        ]);

        // MOCK validation: Normally we verify $request->identity_token with Apple
        $user = $this->authService->findOrCreateSocialUser(
            'apple',
            $request->provider_id,
            $request->email,
            $request->name
        );

        $token = $user->createToken('auth_token')->plainTextToken;

        return $this->success([
            'user' => new AuthUserResource($user),
            'token' => $token,
        ], 'Logged in with Apple successfully.');
    }
}
