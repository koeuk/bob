<?php

namespace App\Actions\Bans;

use App\Models\ActivityLog;
use App\Models\Ban;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

/**
 * The single "ban a user" implementation.
 *
 * Reached from two places, which differ only in the activity-log name:
 *   POST /admin/users/{user}/ban  → user.ban
 *   POST /admin/bans              → ban.create
 *
 * The policy check lives here rather than in the controllers because the JSON
 * API previously skipped it entirely, letting callers ban users the BanPolicy
 * forbids (e.g. super admins).
 */
class IssueBan
{
    /** Rules for banning an already-resolved user. */
    public static function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'max:2000'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    /** Rules for the standalone bans endpoint, which resolves the user itself. */
    public static function rulesWithUser(): array
    {
        return ['user_uuid' => ['required', 'uuid', 'exists:users,uuid']] + self::rules();
    }

    public function handle(User $user, array $data, User $actor, string $logAction = 'user.ban'): Ban
    {
        Gate::forUser($actor)->authorize('ban', $user);

        $ban = Ban::create([
            'user_id' => $user->id,
            'banned_by' => $actor->id,
            'reason' => $data['reason'],
            'expires_at' => $data['expires_at'] ?? null,
        ]);

        // Revoke API tokens so the ban takes effect immediately.
        $user->tokens()?->delete();

        ActivityLog::record($logAction, $user, null, $ban->only(['reason', 'expires_at']));

        return $ban;
    }
}
