<?php
namespace App\Services\Chat;
use App\Models\Message;
use App\Models\User;
use App\Events\MessageSent;

class ChatService {
    public function sendMessage(User $sender, User $receiver, string $message, ?int $articleId = null): Message {
        $msg = Message::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'article_id' => $articleId,
            'message' => $message,
        ]);
        event(new MessageSent($msg));
        return $msg;
    }

    public function markAsRead(Message $message): void {
        $message->update(['is_read' => true, 'read_at' => now()]);
    }

    public function getConversation(User $user1, User $user2, ?int $articleId = null, ?int $perPage = 50) {
        $query = Message::where(function($q) use ($user1, $user2) {
                $q->where('sender_id', $user1->id)->where('receiver_id', $user2->id);
            })->orWhere(function($q) use ($user1, $user2) {
                $q->where('sender_id', $user2->id)->where('receiver_id', $user1->id);
            });

        if ($articleId) {
            $query->where('article_id', $articleId);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }
}
