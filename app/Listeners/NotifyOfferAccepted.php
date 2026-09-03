<?php
namespace App\Listeners;

use App\Events\OfferAccepted;
use App\Services\Notification\FirebaseNotificationService;

class NotifyOfferAccepted
{
    public function __construct(private FirebaseNotificationService $notif) {}

    public function handle(OfferAccepted $event): void
    {
        $offer = $event->offer;
        $this->notif->send(
            $offer->proposer(),
            'Offre acceptée',
            'Votre offre de ' . number_format($offer->montant, 0, ',', ' ') . ' GNF pour « ' . $offer->article->titre . ' » a été acceptée !',
            'offre_acceptee',
            ['offer_id' => $offer->id, 'article_id' => $offer->article_id, 'order_id' => $offer->order_id]
        );
    }
}
