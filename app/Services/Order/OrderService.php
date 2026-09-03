<?php

namespace App\Services\Order;

use App\Events\OrderCreated;
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
}
