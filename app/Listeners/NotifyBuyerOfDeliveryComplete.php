<?php
namespace App\Listeners;
use App\Events\DeliveryCompleted;
use App\Services\Notification\FirebaseNotificationService;

class NotifyBuyerOfDeliveryComplete
{
    public function __construct(private FirebaseNotificationService $notif) {}

    public function handle(DeliveryCompleted $event): void
    {
        $this->notif->send(
            $event->delivery->order->buyer,
            'Livraison terminée',
            'Votre commande #'.$event->delivery->order->reference.' a été livrée avec succès.',
            'livraison.terminee',
            ['delivery_id' => $event->delivery->id]
        );
    }
}
