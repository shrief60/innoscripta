<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

// i'm  just building the login functionality to complete the task not to build a structureed and scalable login system
class AuthController extends Controller
{
    /**
     * Login user and return API token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // Delete old tokens (optional - for single session per user)
        // $user->tokens()->delete();

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'token' => $token,
        ]);
    }

    /**
     * Logout user (revoke current token)
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Logged out successfully',
        ]);
    }

    /**
     * Get authenticated user info
     */
    public function me(Request $request)
    {
        $user = $request->user();
        $user->load('preference.preferredSources', 'preference.preferredCategories');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'preferences' => [
                'sources' => $user->preference?->preferredSources->pluck('slug') ?? [],
                'categories' => $user->preference?->preferredCategories->pluck('slug') ?? [],
                'authors' => $user->preference?->preferred_authors ?? [],
                'articles_per_page' => $user->preference?->articles_per_page ?? 20,
                'default_sort' => $user->preference?->default_sort ?? 'published_at',
                'default_order' => $user->preference?->default_order ?? 'desc',
            ],
        ]);
    }
}
