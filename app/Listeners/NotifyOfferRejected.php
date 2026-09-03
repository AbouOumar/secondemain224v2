<?php
namespace App\Listeners;

use App\Events\OfferRejected;
use App\Services\Notification\FirebaseNotificationService;

class NotifyOfferRejected
{
    public function __construct(private FirebaseNotificationService $notif) {}

    public function handle(OfferRejected $event): void
    {
        $offer = $event->offer;
        $this->notif->send(
            $offer->proposer(),
            'Offre refusée',
            'Votre offre de ' . number_format($offer->montant, 0, ',', ' ') . ' GNF pour « ' . $offer->article->titre . ' » a été refusée.',
            'offre_refusee',
            ['offer_id' => $offer->id, 'article_id' => $offer->article_id]
        );
    }
}
