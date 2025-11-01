<?php

declare(strict_types=1);

namespace B7s\LaraInk\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;

final class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if (!Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = Auth::user();
        $token = $user->createToken('lara-ink-token', ['*'], now()->addSeconds(
            (int) ink_config('auth.token_ttl', 900)
        ))->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    public function isAuthenticated(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'authenticated' => false,
            ], 401);
        }

        $token = $user->createToken('lara-ink-token', ['*'], now()->addSeconds(
            (int) ink_config('auth.token_ttl', 900)
        ))->plainTextToken;

        return response()->json([
            'authenticated' => true,
            'token' => $token,
            'user' => $user,
        ]);
    }
}
