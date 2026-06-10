<?php
namespace App\Listeners;

use App\Events\DeliveryAccepted;
use App\Services\Notification\FirebaseNotificationService;

class NotifySellerOfDeliveryAccepted
{
    public function __construct(private FirebaseNotificationService $notif) {}

    public function handle(DeliveryAccepted $event): void
    {
        $seller = $event->delivery->order->seller;
        $this->notif->send(
            $seller,
            'Livraison acceptée',
            'Votre commande #'.$event->delivery->order->reference.' a été acceptée par un livreur.',
            'livraison.acceptee',
            ['delivery_id' => $event->delivery->id]
        );
    }
}
