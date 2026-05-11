<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Notifications\Notification;

class FriendRequestAccepted extends Notification
{
    public function __construct(
        public User $acceptor,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'         => 'friend_accepted',
            'actor_name'   => $this->acceptor->name,
            'actor_uuid'   => $this->acceptor->uuid,
            'actor_avatar' => $this->acceptor->avatar,
        ];
    }
}
