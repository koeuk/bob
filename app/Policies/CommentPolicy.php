<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isModerator();
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->isModerator() || $comment->user_id === $user->id;
    }
}
