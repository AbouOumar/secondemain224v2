<?php
namespace App\Listeners;

use App\Events\OrderCreated;
use App\Services\Notification\FirebaseNotificationService;

class NotifySellerOfNewOrder
{
    public function __construct(private FirebaseNotificationService $notif) {}

    public function handle(OrderCreated $event): void
    {
        $this->notif->send(
            $event->order->seller,
            'Nouvelle commande',
            'Votre article "'.$event->order->article->titre.'" a été commandé.',
            'nouvelle.commande',
            ['order_id' => $event->order->id]
        );
    }
}
