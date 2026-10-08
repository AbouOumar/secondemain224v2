<?php
namespace App\Listeners;

use App\Events\MessageSent;
use App\Jobs\SendPushNotification;
use App\Services\Notification\PushRoute;
use Illuminate\Support\Str;

/**
 * Push à chaque nouveau message (contrairement à l'e-mail, pas de limite :
 * c'est le comportement attendu d'une messagerie).
 */
class PushReceiverOfNewMessage
{
    public function handle(MessageSent $event): void
    {
        $message = $event->message;
        $receiver = $message->receiver;
        $sender = $message->sender;

        if (!$receiver || !$sender || !$receiver->deviceTokens()->exists()) {
            return;
        }

        SendPushNotification::dispatch(
            $receiver,
            "Nouveau message de {$sender->name}",
            Str::limit($message->message, 100),
            ['type' => 'nouveau_message', 'route' => PushRoute::conversation($sender->id, $message->article_id, $sender->name)],
        );
    }
}
