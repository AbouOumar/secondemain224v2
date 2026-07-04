<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\Article;
use App\Models\Delivery;
use App\Http\Resources\OrderResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Events\OrderCreated;

class OrderController extends Controller
{
    public function store(StoreOrderRequest $request) {
        $article = Article::findOrFail($request->article_id);

        if ($article->statut === 'vendu') {
            return response()->json(['message' => 'Cet article a déjà été vendu.'], 409);
        }
        $total = $article->prix;
        $deliveryPrix = 0;

        if ($request->with_delivery && $article->with_delivery) {
            $deliveryPrix = $article->delivery_prix ?? 0;
            $total += $deliveryPrix;
        }

        $order = Order::create([
            'reference' => 'CMD-' . strtoupper(Str::random(6)),
            'buyer_id' => $request->user()->id,
            'seller_id' => $article->user_id,
            'article_id' => $article->id,
            'prix_article' => $article->prix,
            'with_delivery' => $request->with_delivery ?? false,
            'delivery_prix' => $deliveryPrix,
            'total' => $total,
            'status' => 'en_attente_paiement',
        ]);

        if ($request->with_delivery && $article->with_delivery) {
            Delivery::create([
                'order_id' => $order->id,
                'pickup_adresse' => $article->localisation,
                'pickup_latitude' => $article->latitude,
                'pickup_longitude' => $article->longitude,
                'delivery_adresse' => $request->user()->localisation ?? '',
                'delivery_latitude' => $request->user()->latitude,
                'delivery_longitude' => $request->user()->longitude,
                'prix' => $deliveryPrix,
                'status' => 'en_attente',
            ]);
        }

        event(new OrderCreated($order));
        $order->load(['article', 'buyer', 'seller', 'delivery']);
        return new OrderResource($order, 201);
    }

    public function index(Request $request) {
        $orders = Order::where('buyer_id', $request->user()->id)
            ->with(['article', 'seller', 'delivery'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return OrderResource::collection($orders);
    }

    public function show($reference) {
        $order = Order::where('reference', $reference)
            ->with(['article', 'buyer', 'seller', 'delivery'])
            ->firstOrFail();
        $this->authorize('view', $order);
        return new OrderResource($order);
    }

    public function cancel(Request $request, string $reference) {
        $order = Order::where('reference', $reference)->firstOrFail();
        $this->authorize('cancel', $order);
        $order->update(['status' => 'annule', 'annule_raison' => $request->raison]);
        return new OrderResource($order);
    }

    public function sellerOrders(Request $request) {
        $orders = Order::where('seller_id', $request->user()->id)
            ->with(['article', 'buyer', 'delivery'])
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return OrderResource::collection($orders);
    }
}
