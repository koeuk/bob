<?php

namespace App\Actions\Users;

use App\Models\ActivityLog;
use App\Models\Report;
use App\Models\User;

/**
 * Gathers everything the admin user-detail screen needs: the user with its
 * roles/bans/counts, reports filed against them, and related activity.
 *
 * @phpstan-type UserProfile array{user: User, reports_against: \Illuminate\Support\Collection, activity: \Illuminate\Support\Collection}
 */
class ShowUser
{
    public function handle(User $user): array
    {
        $user->load([
            'roles',
            'bans' => fn ($q) => $q->with('bannedBy:id,uuid,name')->latest(),
        ])->loadCount(['posts', 'comments', 'reportsFiled']);
        $user->append('role');

        $reportsAgainst = Report::where('reportable_type', User::class)
            ->where('reportable_id', $user->id)
            ->with('reporter:id,uuid,name')
            ->latest()
            ->limit(20)
            ->get();

        $activity = ActivityLog::where(function ($q) use ($user) {
            $q->where('admin_id', $user->id)
                ->orWhere(function ($q2) use ($user) {
                    $q2->where('target_type', User::class)->where('target_id', $user->id);
                });
        })
            ->with('admin:id,uuid,name')
            ->latest()
            ->limit(30)
            ->get();

        return [
            'user' => $user,
            'reports_against' => $reportsAgainst,
            'activity' => $activity,
        ];
    }
}
