<?php
namespace App\Listeners;
use App\Events\OrderCreated;
use App\Services\Delivery\DeliveryAssignmentService;
use App\Services\Notification\FirebaseNotificationService;

class NotifyNearbyRiders
{
    public function __construct(
        private DeliveryAssignmentService $deliveryAssignment,
        private FirebaseNotificationService $notif
    ) {}

    public function handle(OrderCreated $event): void
    {
        $order = $event->order;
        $delivery = $order->delivery;

        if (!$delivery || !$order->with_delivery) {
            return;
        }

        $rider = $this->deliveryAssignment->assignDelivery($delivery);

        if ($rider) {
            $this->notif->send(
                $rider,
                'Nouvelle livraison disponible',
                'Une livraison est disponible près de chez vous pour la commande #'.$order->reference.'.',
                'livraison.disponible',
                ['delivery_id' => $delivery->id, 'order_reference' => $order->reference]
            );
        }
    }
}
