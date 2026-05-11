<?php

namespace App\Notifications;

use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Notifications\Notification;

class FriendRequestReceived extends Notification
{
    public function __construct(
        public User $sender,
        public FriendRequest $friendRequest,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'type'              => 'friend_request',
            'actor_name'        => $this->sender->name,
            'actor_uuid'        => $this->sender->uuid,
            'actor_avatar'      => $this->sender->avatar,
            'friend_request_id' => $this->friendRequest->id,
        ];
    }
}
