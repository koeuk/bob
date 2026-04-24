<?php

namespace App\Policies;

use App\Models\Report;
use App\Models\User;

class ReportPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isModerator();
    }

    public function view(User $user, Report $report): bool
    {
        return $user->isModerator() || $report->reporter_id === $user->id;
    }

    public function review(User $user): bool
    {
        return $user->isModerator();
    }

    public function resolve(User $user): bool
    {
        return $user->isModerator();
    }

    public function dismiss(User $user): bool
    {
        return $user->isModerator();
    }
}
