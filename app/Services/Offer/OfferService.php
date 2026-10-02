<?php

namespace App\Services\Offer;

use App\Events\OfferAccepted;
use App\Events\OfferCountered;
use App\Events\OfferMade;
use App\Events\OfferRejected;
use App\Exceptions\OfferException;
use App\Models\Article;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use App\Services\Order\OrderService;
use Illuminate\Support\Facades\DB;

/**
 * Négociation de prix sur une annonce : offre initiale, contre-offres
 * illimitées (chaque round expire après 48h sans réponse), acceptation
 * (crée la commande au prix négocié) ou refus.
 */
class OfferService
{
    private const MIN_OFFER_RATIO = 0.5;
    public const EXPIRY_HOURS = 48;

    public function __construct(private OrderService $orders) {}

    public function make(User $buyer, Article $article, int $montant): Offer
    {
        if ($buyer->id === $article->user_id) {
            throw new OfferException('Vous ne pouvez pas faire une offre sur votre propre annonce.');
        }

        if ($article->statut === 'vendu' || ! $article->is_published) {
            throw new OfferException('Cette annonce n\'est plus disponible.');
        }

        $this->assertMinimumBuyerAmount($article, $montant);

        $existing = $this->activeThread($article, $buyer);
        if ($existing) {
            throw new OfferException('Une négociation est déjà en cours sur cette annonce.');
        }

        $offer = Offer::create([
            'article_id' => $article->id,
            'buyer_id' => $buyer->id,
            'seller_id' => $article->user_id,
            'parent_offer_id' => null,
            'montant' => $montant,
            'made_by' => 'acheteur',
            'status' => 'en_attente',
            'expires_at' => now()->addHours(self::EXPIRY_HOURS),
        ]);

        event(new OfferMade($offer));

        return $offer;
    }

    public function counter(Offer $current, User $author, int $montant): Offer
    {
        $this->assertActionable($current);

        if ($author->id !== $current->recipient()->id) {
            throw new OfferException('Vous n\'êtes pas destinataire de cette offre.');
        }

        $madeBy = $author->id === $current->seller_id ? 'vendeur' : 'acheteur';

        if ($madeBy === 'acheteur') {
            $this->assertMinimumBuyerAmount($current->article, $montant);
        }

        return DB::transaction(function () use ($current, $montant, $madeBy) {
            $current->update(['status' => 'contree']);

            $offer = Offer::create([
                'article_id' => $current->article_id,
                'buyer_id' => $current->buyer_id,
                'seller_id' => $current->seller_id,
                'parent_offer_id' => $current->id,
                'montant' => $montant,
                'made_by' => $madeBy,
                'status' => 'en_attente',
                'expires_at' => now()->addHours(self::EXPIRY_HOURS),
            ]);

            event(new OfferCountered($offer));

            return $offer;
        });
    }

    public function accept(Offer $offer, User $actor): Order
    {
        $this->assertActionable($offer);

        if ($actor->id !== $offer->recipient()->id) {
            throw new OfferException('Vous n\'êtes pas destinataire de cette offre.');
        }

        return DB::transaction(function () use ($offer) {
            $order = $this->orders->createForArticle(
                $offer->buyer,
                $offer->article,
                withDelivery: false,
                prixOverride: (int) $offer->montant,
            );

            $offer->update(['status' => 'acceptee', 'order_id' => $order->id]);

            event(new OfferAccepted($offer));

            return $order;
        });
    }

    public function reject(Offer $offer, User $actor): void
    {
        $this->assertActionable($offer);

        if ($actor->id !== $offer->recipient()->id) {
            throw new OfferException('Vous n\'êtes pas destinataire de cette offre.');
        }

        $offer->update(['status' => 'refusee']);

        event(new OfferRejected($offer));
    }

    public function cancel(Offer $offer, User $actor): void
    {
        $this->assertActionable($offer);

        if ($actor->id !== $offer->proposer()->id) {
            throw new OfferException('Seul l\'auteur de la proposition peut l\'annuler.');
        }

        $offer->update(['status' => 'annulee']);
    }

    /**
     * Marque expirées toutes les offres en attente dont le délai est
     * dépassé. Appelée par la commande planifiée offers:expire-stale.
     */
    public function expireStale(): int
    {
        return Offer::where('status', 'en_attente')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expiree']);
    }

    private function assertMinimumBuyerAmount(Article $article, int $montant): void
    {
        $minimum = (int) round($article->prix * self::MIN_OFFER_RATIO);
        if ($montant < $minimum) {
            throw new OfferException(
                'Le montant proposé est trop bas (minimum ' . number_format($minimum, 0, ',', ' ') . ' GNF).'
            );
        }
    }

    private function assertActionable(Offer $offer): void
    {
        if ($offer->status->value !== 'en_attente') {
            throw new OfferException('Cette offre n\'est plus modifiable.');
        }
    }

    private function activeThread(Article $article, User $buyer): ?Offer
    {
        return Offer::where('article_id', $article->id)
            ->where('buyer_id', $buyer->id)
            ->whereIn('status', ['en_attente', 'contree'])
            ->latest()
            ->first();
    }
}
