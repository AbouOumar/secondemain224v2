<?php
namespace App\Listeners;

use App\Enums\EmailCategory;
use App\Events\MessageSent;
use App\Notifications\ActivityNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Prévient le destinataire d'un message par e-mail, au plus une fois par
 * heure et par conversation pour ne pas l'inonder pendant un échange.
 */
class EmailReceiverOfNewMessage
{
    public const THROTTLE_SECONDS = 3600;

    public function handle(MessageSent $event): void
    {
        $message = $event->message;
        $receiver = $message->receiver;
        $sender = $message->sender;

        if (!$receiver || !$sender || !$receiver->wantsEmailFor(EmailCategory::Messages)) {
            return;
        }

        $key = "message_email:{$receiver->id}:{$sender->id}:" . ($message->article_id ?? 0);
        if (!Cache::add($key, true, self::THROTTLE_SECONDS)) {
            return;
        }

        $about = $message->article ? ' à propos de « ' . $message->article->titre . ' »' : '';

        $receiver->notify(new ActivityNotification(
            EmailCategory::Messages,
            "Nouveau message de {$sender->name}",
            "{$sender->name} vous a écrit{$about} : « " . Str::limit($message->message, 200) . ' »',
            route('messages.show', array_filter(['user' => $sender->id, 'article' => $message->article_id])),
            'Répondre',
        ));
    }
}
