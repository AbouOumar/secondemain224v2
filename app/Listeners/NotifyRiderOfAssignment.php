<?php
namespace App\Listeners;
use App\Events\DeliveryAssigned;
use App\Services\Notification\FirebaseNotificationService;

class NotifyRiderOfAssignment
{
    public function __construct(private FirebaseNotificationService $notif) {}

    public function handle(DeliveryAssigned $event): void
    {
        $this->notif->send(
            $event->delivery->rider,
            'Nouvelle livraison',
            'Une nouvelle mission de livraison vous a été attribuée.',
            'livraison.attribuee',
            ['delivery_id' => $event->delivery->id]
        );
    }
}
