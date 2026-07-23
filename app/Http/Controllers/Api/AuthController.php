<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

/**
 * @group Authentication
 *
 * Register, login, logout, and manage your profile.
 */
class AuthController extends Controller
{
    /**
     * Register
     *
     * Create a new account and receive a bearer token.
     *
     * @unauthenticated
     * @bodyParam name string required Display name. Example: Jane Doe
     * @bodyParam email string required Email address. Example: jane@example.com
     * @bodyParam password string required Password (min 8 chars). Example: secret123
     * @bodyParam password_confirmation string required Must match password. Example: secret123
     *
     * @response 201 {
     *   "user": { "id": 1, "uuid": "...", "name": "Jane Doe", "email": "jane@example.com", "role": "user" },
     *   "token": "1|abc123..."
     * }
     */
    public function register(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $user = User::create($data);
        $user->assignRole('user');
        $token = $user->createToken('api')->plainTextToken;

        return response()->json(['user' => $user->append('role'), 'token' => $token], 201);
    }

    /**
     * Login
     *
     * Authenticate and receive a bearer token.
     *
     * @unauthenticated
     * @bodyParam email string required Example: admin@example.com
     * @bodyParam password string required Example: password
     *
     * @response 200 {
     *   "user": { "id": 2, "uuid": "...", "name": "Super Admin", "email": "admin@example.com", "role": "super_admin" },
     *   "token": "1|abc123..."
     * }
     * @response 401 { "message": "Invalid credentials." }
     * @response 403 { "message": "Your account has been banned." }
     */
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($data)) {
            return response()->json(['message' => 'Invalid credentials.'], 401);
        }

        $user = Auth::user();

        if ($user->isBanned()) {
            Auth::logout();
            return response()->json(['message' => 'Your account has been banned.'], 403);
        }

        $token = $user->createToken('api')->plainTextToken;

        return response()->json(['user' => $user->append('role'), 'token' => $token]);
    }

    /**
     * Logout
     *
     * Revoke the current bearer token.
     *
     * @response 200 { "message": "Logged out." }
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    /**
     * Get profile
     *
     * Return the authenticated user's profile.
     *
     * @response 200 {
     *   "id": 2, "uuid": "...", "name": "Super Admin", "email": "admin@example.com", "role": "super_admin",
     *   "created_at": "2026-01-01T00:00:00.000000Z"
     * }
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json($request->user()->append('role'));
    }

    /**
     * Update profile
     *
     * Update name and/or email. Changing email resets email verification.
     *
     * @bodyParam name string Display name. Example: Jane Doe
     * @bodyParam email string Email address. Example: jane@example.com
     *
     * @response 200 { "id": 1, "name": "Jane Doe", "email": "jane@example.com" }
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $data = $request->validate([
            'name'   => ['sometimes', 'string', 'max:255'],
            'email'  => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'avatar' => ['sometimes', 'image', 'max:4096'],
            'cover'  => ['sometimes', 'image', 'max:8192'],
        ]);

        if (isset($data['email']) && $data['email'] !== $user->email) {
            $data['email_verified_at'] = null;
        }

        // Delete the replaced files so uploads don't accumulate. Read the raw
        // columns — the accessors return /storage/... URLs, which never match a
        // disk path.
        if ($request->hasFile('avatar')) {
            if ($old = $user->getRawOriginal('avatar')) {
                Storage::disk('public')->delete($old);
            }
            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        if ($request->hasFile('cover')) {
            if ($old = $user->getRawOriginal('cover')) {
                Storage::disk('public')->delete($old);
            }
            $data['cover'] = $request->file('cover')->store('covers', 'public');
        }

        $user->update($data);

        return response()->json($user->fresh()->append('role'));
    }

    /**
     * Change password
     *
     * @bodyParam current_password string required Your current password. Example: oldpassword
     * @bodyParam password string required New password (min 8 chars). Example: newpassword
     * @bodyParam password_confirmation string required Must match password. Example: newpassword
     *
     * @response 200 { "message": "Password updated." }
     */
    public function password(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        $request->user()->update(['password' => $request->password]);

        return response()->json(['message' => 'Password updated.']);
    }

    /**
     * Delete account
     *
     * Permanently delete the authenticated user's account.
     *
     * @bodyParam password string required Password confirmation. Example: password
     *
     * @response 200 { "message": "Account deleted." }
     */
    public function destroy(Request $request): JsonResponse
    {
        $request->validate(['password' => ['required', 'current_password']]);

        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->json(['message' => 'Account deleted.']);
    }
}
