<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Order;
use App\Models\Delivery;
use App\Events\OrderCreated;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function create(Request $request, $articleId, $delivery)
    {
        // Validate the article exists
        $article = Article::findOrFail($articleId);

        // Validate delivery parameter
        $delivery = (int)$delivery === 1;

        // Create the order
        $order = Order::create([
            'reference' => 'CMD-' . strtoupper(Str::random(6)),
            'buyer_id' => Auth::id(),
            'seller_id' => $article->user_id,
            'article_id' => $article->id,
            'prix_article' => $article->prix,
            'with_delivery' => $delivery && $article->with_delivery,
            'delivery_prix' => $delivery && $article->with_delivery ? ($article->delivery_prix ?? 0) : 0,
            'total' => $article->prix + (($delivery && $article->with_delivery) ? ($article->delivery_prix ?? 0) : 0),
            'status' => 'en_attente_paiement',
        ]);

        // Create delivery if applicable
        if ($order->with_delivery && $article->with_delivery) {
            Delivery::create([
                'order_id' => $order->id,
                'pickup_adresse' => $article->localisation ?? 'Adresse non spécifiée',
                'pickup_latitude' => $article->latitude ?? 0,
                'pickup_longitude' => $article->longitude ?? 0,
                'delivery_adresse' => Auth::user()->localisation ?? 'Adresse non spécifiée',
                'delivery_latitude' => Auth::user()->latitude ?? 0,
                'delivery_longitude' => Auth::user()->longitude ?? 0,
                'prix' => $order->delivery_prix,
                'status' => 'en_attente',
            ]);
        }

        event(new OrderCreated($order));

        return redirect()->route('payment.show', $order);
    }
}