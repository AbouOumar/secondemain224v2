<?php

namespace App\Services\Order;

use App\Events\OrderCreated;
use App\Exceptions\OrderException;
use Illuminate\Support\Facades\DB;
use App\Models\Article;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Point d'entrée unique pour créer une commande à partir d'une annonce,
 * que ce soit au prix affiché (achat direct) ou à un prix négocié
 * (acceptation d'une offre — voir OfferService::accept()).
 */
class OrderService
{
    public function createForArticle(User $buyer, Article $article, bool $withDelivery, ?int $prixOverride = null): Order
    {
        $prixArticle = $prixOverride ?? (int) $article->prix;
        $deliveryApplies = $withDelivery && $article->with_delivery;
        $deliveryPrix = $deliveryApplies ? (int) ($article->delivery_prix ?? 0) : 0;

        $order = Order::create([
            'reference' => 'CMD-' . strtoupper(Str::random(6)),
            'buyer_id' => $buyer->id,
            'seller_id' => $article->user_id,
            'article_id' => $article->id,
            'prix_article' => $prixArticle,
            'with_delivery' => $deliveryApplies,
            'delivery_prix' => $deliveryPrix,
            'total' => $prixArticle + $deliveryPrix,
            'status' => 'en_attente_paiement',
        ]);

        if ($deliveryApplies) {
            Delivery::create([
                'order_id' => $order->id,
                'pickup_adresse' => $article->localisation ?? 'Adresse non spécifiée',
                'pickup_latitude' => $article->latitude ?? 0,
                'pickup_longitude' => $article->longitude ?? 0,
                'delivery_adresse' => $buyer->localisation ?? 'Adresse non spécifiée',
                'delivery_latitude' => $buyer->latitude ?? 0,
                'delivery_longitude' => $buyer->longitude ?? 0,
                'prix' => $deliveryPrix,
                'status' => 'en_attente',
            ]);
        }

        event(new OrderCreated($order));

        return $order;
    }

    /**
     * Ajoute la livraison à une commande non payée (typiquement née d'une offre acceptée, qui n'en a pas),
     * ou met à jour l'adresse. Le total et la livraison suivent le tarif de l'annonce.
     *
     * @throws OrderException annonce non livrable (422) ou paiement déjà lancé (409)
     */
    public function setDelivery(Order $order, string $address, ?float $latitude = null, ?float $longitude = null): Order
    {
        return DB::transaction(function () use ($order, $address, $latitude, $longitude) {
            $order = $this->lockEditable($order);
            $article = $order->article;
            if (! $article->with_delivery) {
                throw new OrderException("Cette annonce n'est pas livrable.", 422);
            }

            $price = (int) ($article->delivery_prix ?? 0);
            $order->update([
                'with_delivery' => true,
                'delivery_prix' => $price,
                'total' => (int) $order->prix_article + $price,
            ]);

            Delivery::updateOrCreate(['order_id' => $order->id], [
                'pickup_adresse' => $article->localisation ?? 'Adresse non spécifiée',
                'pickup_latitude' => $article->latitude ?? 0,
                'pickup_longitude' => $article->longitude ?? 0,
                'delivery_adresse' => $address,
                'delivery_latitude' => $latitude ?? $order->buyer->latitude ?? 0,
                'delivery_longitude' => $longitude ?? $order->buyer->longitude ?? 0,
                'prix' => $price,
                'status' => 'en_attente',
            ]);

            return $order->fresh(['article', 'buyer', 'seller', 'delivery']);
        });
    }

    /** L'acheteur renonce à la livraison : remise en main propre, total sans frais de livraison. */
    public function removeDelivery(Order $order): Order
    {
        return DB::transaction(function () use ($order) {
            $order = $this->lockEditable($order);
            Delivery::where('order_id', $order->id)->delete();
            $order->update(['with_delivery' => false, 'delivery_prix' => 0, 'total' => (int) $order->prix_article]);

            return $order->fresh(['article', 'buyer', 'seller', 'delivery']);
        });
    }

    /** Verrouille la commande et refuse tout changement une fois un paiement lancé ou encaissé. */
    private function lockEditable(Order $order): Order
    {
        $locked = Order::whereKey($order->id)->lockForUpdate()->firstOrFail();
        if ($locked->status->value !== 'en_attente_paiement') {
            throw new OrderException('Cette commande ne peut plus être modifiée.', 403);
        }
        // Un paiement déjà lancé porte l'ancien montant : le changer le rendrait incohérent.
        if ($locked->payments()->whereIn('status', ['en_attente', 'succes'])->exists()) {
            throw new OrderException('Un paiement est déjà en cours pour cette commande : terminez-le ou attendez son échec avant de modifier la livraison.', 409);
        }

        return $locked;
    }
}
