<?php
namespace App\Listeners;

use App\Events\DeliveryDelivered;
use App\Services\Notification\FirebaseNotificationService;

class NotifyBuyerOfDeliveryDelivered
{
    public function __construct(private FirebaseNotificationService $notif) {}

    public function handle(DeliveryDelivered $event): void
    {
        $this->notif->send(
            $event->delivery->order->buyer,
            'Colis livré',
            'Votre commande #'.$event->delivery->order->reference.' a été livrée. Veuillez confirmer la réception pour finaliser l\'opération.',
            'livraison.livree',
            ['delivery_id' => $event->delivery->id]
        );
    }
}
