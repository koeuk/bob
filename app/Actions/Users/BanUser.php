<?php

namespace App\Actions\Users;

use App\Models\ActivityLog;
use App\Models\Ban;
use App\Models\User;

class BanUser
{
    /** Validation rules shared by every surface that bans a user. */
    public static function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    public function handle(User $user, array $data, User $actor): Ban
    {
        $ban = Ban::create([
            'user_id' => $user->id,
            'banned_by' => $actor->id,
            'reason' => $data['reason'],
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        // Revoke API tokens so the ban takes effect immediately.
        $user->tokens()?->delete();

        ActivityLog::record('user.ban', $user, null, $ban->only(['reason', 'expires_at']));

        return $ban;
    }
}
