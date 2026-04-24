<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Auth::getProvider()->validateCredentials($user, $data)) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials.']);
        }

        if (! $user->isModerator()) {
            throw ValidationException::withMessages(['email' => 'This account has no admin access.']);
        }

        if ($user->isBanned()) {
            throw ValidationException::withMessages(['email' => 'Account is banned.']);
        }

        $abilities = match ($user->role) {
            'super_admin' => ['*'],
            'admin' => ['admin'],
            'moderator' => ['moderator'],
            default => [],
        };

        $token = $user->createToken($data['device_name'] ?? 'admin-web', $abilities);

        ActivityLog::record('admin.login', $user);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $user->only(['uuid', 'name', 'email', 'role']),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out.']);
    }

    public function user(Request $request): JsonResponse
    {
        $u = $request->user();

        return response()->json([
            'user' => $u->only(['uuid', 'name', 'email', 'role']),
            'permissions' => [
                'is_super_admin' => $u->isSuperAdmin(),
                'is_admin' => $u->isAdmin(),
                'is_moderator' => $u->isModerator(),
            ],
        ]);
    }
}
