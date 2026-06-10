<?php
namespace App\Listeners;
use App\Events\OrderCreated;
use App\Services\Notification\FirebaseNotificationService;

class NotifyBuyerOfOrderConfirmation
{
    public function __construct(private FirebaseNotificationService $notif) {}

    public function handle(OrderCreated $event): void
    {
        $this->notif->send(
            $event->order->buyer,
            'Achat validé',
            'Votre commande #'.$event->order->reference.' a bien été enregistrée.',
            'achat.valide',
            ['order_reference' => $event->order->reference]
        );
    }
}
