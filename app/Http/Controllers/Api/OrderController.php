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
use App\Services\Escrow\EscrowService;
use App\Services\Order\OrderService;
use App\Exceptions\OrderException;

class OrderController extends Controller
{
    public function __construct(private EscrowService $escrow) {}

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

            $deliveryAdresse = $request->delivery_adresse ?? $request->user()->localisation;
            $deliveryLatitude = $request->delivery_latitude ?? $request->user()->latitude;
            $deliveryLongitude = $request->delivery_longitude ?? $request->user()->longitude;

            if (! $deliveryAdresse) {
                return response()->json([
                    'message' => 'Une adresse de livraison est requise.',
                    'errors' => ['delivery_adresse' => ['Veuillez indiquer une adresse de livraison.']],
                ], 422);
            }
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
                'pickup_latitude' => $article->latitude ?? 0,
                'pickup_longitude' => $article->longitude ?? 0,
                'delivery_adresse' => $deliveryAdresse,
                'delivery_latitude' => $deliveryLatitude ?? 0,
                'delivery_longitude' => $deliveryLongitude ?? 0,
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

        if ($order->escrow && $order->escrow->status->value === 'retenu') {
            $this->escrow->refund($order->escrow, $request->raison ?? 'annulation_commande');
        }

        $order->update(['status' => 'annule', 'annule_raison' => $request->raison]);
        return new OrderResource($order);
    }

    /**
     * Confirmation de réception par l'acheteur pour une commande sans
     * livraison (remise en main propre) : déclenche la libération de
     * l'escrow au vendeur.
     */
    public function confirmReceipt(Request $request, string $reference) {
        $order = Order::where('reference', $reference)->firstOrFail();
        $this->authorize('confirmReceipt', $order);

        $order->update(['status' => 'livre']);

        if ($order->escrow) {
            $this->escrow->release($order->escrow, 'confirmation_reception');
        }

        return new OrderResource($order);
    }

    /** Ajoute la livraison (ou change l'adresse) d'une commande non payée, par exemple née d'une offre acceptée. */
    public function setDelivery(Request $request, string $reference, OrderService $orders) {
        $order = Order::where('reference', $reference)->firstOrFail();
        $this->authorize('changeDelivery', $order);

        $data = $request->validate([
            'delivery_adresse' => 'required|string|max:255',
            'delivery_latitude' => 'nullable|numeric|between:-90,90',
            'delivery_longitude' => 'nullable|numeric|between:-180,180',
        ]);

        try {
            $order = $orders->setDelivery($order, $data['delivery_adresse'], $data['delivery_latitude'] ?? null, $data['delivery_longitude'] ?? null);
        } catch (OrderException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        return new OrderResource($order);
    }

    public function removeDelivery(string $reference, OrderService $orders) {
        $order = Order::where('reference', $reference)->firstOrFail();
        $this->authorize('changeDelivery', $order);

        try {
            $order = $orders->removeDelivery($order);
        } catch (OrderException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

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
