<?php

namespace App\Notifications;

use App\Models\Comment;
use App\Models\Post;
use App\Models\User;
use Illuminate\Notifications\Notification;

class PostCommented extends Notification
{
    public function __construct(
        public User $actor,
        public Post $post,
        public Comment $comment,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'            => 'post_commented',
            'actor_name'      => $this->actor->name,
            'actor_uuid'      => $this->actor->uuid,
            'post_uuid'       => $this->post->uuid,
            'post_excerpt'    => mb_substr(strip_tags($this->post->body ?? ''), 0, 80),
            'comment_excerpt' => mb_substr($this->comment->body, 0, 80),
        ];
    }
}
