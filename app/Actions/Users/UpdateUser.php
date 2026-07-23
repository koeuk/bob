<?php

namespace App\Actions\Users;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class UpdateUser
{
    /** Validation rules shared by every surface that updates a user. */
    public static function rules(User $user): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['sometimes', 'nullable', 'string', 'min:8'],
            'avatar' => ['sometimes', 'nullable', 'image', 'max:2048'],
        ];
    }

    public function handle(User $user, array $data, ?UploadedFile $avatar = null, bool $removeAvatar = false): User
    {
        $before = $user->only(['name', 'email']);

        $attrs = array_filter([
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'password' => ! empty($data['password']) ? $data['password'] : null,
        ], fn ($v) => $v !== null);

        // NB: read the raw column, not $user->avatar — the accessor returns a
        // public URL (/storage/...), which would never match a disk path and so
        // silently failed to delete the old file, orphaning uploads.
        $currentAvatar = $user->getRawOriginal('avatar');

        if ($avatar) {
            if ($currentAvatar) {
                Storage::disk('public')->delete($currentAvatar);
            }
            $attrs['avatar'] = $avatar->store('avatars', 'public');
        } elseif ($removeAvatar && $currentAvatar) {
            Storage::disk('public')->delete($currentAvatar);
            $attrs['avatar'] = null;
        }

        $user->update($attrs);

        ActivityLog::record('user.update', $user, $before, $user->only(['name', 'email']));

        return $user;
    }
}
