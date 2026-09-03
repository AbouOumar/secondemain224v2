<?php
namespace App\Listeners;

use App\Events\OfferMade;
use App\Services\Notification\FirebaseNotificationService;

class NotifySellerOfNewOffer
{
    public function __construct(private FirebaseNotificationService $notif) {}

    public function handle(OfferMade $event): void
    {
        $offer = $event->offer;
        $this->notif->send(
            $offer->seller,
            'Nouvelle offre reçue',
            $offer->buyer->name . ' propose ' . number_format($offer->montant, 0, ',', ' ') . ' GNF pour « ' . $offer->article->titre . ' ».',
            'nouvelle_offre',
            ['offer_id' => $offer->id, 'article_id' => $offer->article_id]
        );
    }
}
