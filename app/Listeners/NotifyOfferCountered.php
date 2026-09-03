<?php
namespace App\Listeners;

use App\Events\OfferCountered;
use App\Services\Notification\FirebaseNotificationService;

class NotifyOfferCountered
{
    public function __construct(private FirebaseNotificationService $notif) {}

    public function handle(OfferCountered $event): void
    {
        $offer = $event->offer;
        $this->notif->send(
            $offer->recipient(),
            'Contre-offre reçue',
            $offer->proposer()->name . ' propose ' . number_format($offer->montant, 0, ',', ' ') . ' GNF pour « ' . $offer->article->titre . ' ».',
            'offre_contree',
            ['offer_id' => $offer->id, 'article_id' => $offer->article_id]
        );
    }
}
