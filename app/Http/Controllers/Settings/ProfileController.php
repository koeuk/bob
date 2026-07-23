<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Show the user's profile settings page.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('settings/profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Update the user's profile settings.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->fill(['name' => $data['name'], 'email' => $data['email']]);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        // NB: read the raw column, not $user->avatar — the accessor returns a
        // public URL (/storage/...), which never matches a disk path, so the
        // old file was never actually deleted.
        $currentAvatar = $user->getRawOriginal('avatar');

        if ($request->hasFile('avatar')) {
            if ($currentAvatar) {
                Storage::disk('public')->delete($currentAvatar);
            }
            $user->avatar = $request->file('avatar')->store('avatars', 'public');
        }

        if (! empty($data['remove_avatar']) && $currentAvatar) {
            Storage::disk('public')->delete($currentAvatar);
            $user->avatar = null;
        }

        $user->save();

        return to_route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
