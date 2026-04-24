<?php

namespace App\Policies;

use App\Models\Post;
use App\Models\User;

class PostPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isModerator();
    }

    public function view(User $user, Post $post): bool
    {
        return $user->isModerator() || $post->user_id === $user->id;
    }

    public function update(User $user, Post $post): bool
    {
        return $user->isModerator() || $post->user_id === $user->id;
    }

    public function delete(User $user, Post $post): bool
    {
        return $user->isModerator() || $post->user_id === $user->id;
    }

    public function flag(User $user): bool
    {
        return $user->isModerator();
    }
}
