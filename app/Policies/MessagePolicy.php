<?php
namespace App\Policies;
use App\Models\User;
use App\Models\Message;

class MessagePolicy
{
    public function view(User $user, Message $message): bool
    {
        return $user->id === $message->sender_id || $user->id === $message->receiver_id;
    }
}
