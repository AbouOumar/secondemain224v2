<?php
namespace App\Http\Controllers\Api;

use App\Exceptions\OfferException;
use App\Http\Controllers\Controller;
use App\Http\Resources\OfferResource;
use App\Http\Resources\OrderResource;
use App\Models\Article;
use App\Models\Offer;
use App\Services\Offer\OfferService;
use Illuminate\Http\Request;

class OfferController extends Controller
{
    public function __construct(private OfferService $offers) {}

    public function index(Request $request) {
        $user = $request->user();
        $offers = Offer::where('buyer_id', $user->id)
            ->orWhere('seller_id', $user->id)
            ->with(['article.images', 'buyer', 'seller'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return OfferResource::collection($offers);
    }

    public function store(Request $request, Article $article) {
        $request->validate(['montant' => 'required|integer|min:1']);

        try {
            $offer = $this->offers->make($request->user(), $article, (int) $request->montant);
        } catch (OfferException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new OfferResource($offer->load(['article', 'buyer', 'seller']));
    }

    public function counter(Request $request, Offer $offer) {
        $request->validate(['montant' => 'required|integer|min:1']);

        try {
            $newOffer = $this->offers->counter($offer, $request->user(), (int) $request->montant);
        } catch (OfferException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new OfferResource($newOffer->load(['article', 'buyer', 'seller']));
    }

    public function accept(Request $request, Offer $offer) {
        try {
            $order = $this->offers->accept($offer, $request->user());
        } catch (OfferException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return new OrderResource($order->load(['article', 'buyer', 'seller']));
    }

    public function reject(Request $request, Offer $offer) {
        try {
            $this->offers->reject($offer, $request->user());
        } catch (OfferException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Offre refusée.']);
    }

    public function cancel(Request $request, Offer $offer) {
        try {
            $this->offers->cancel($offer, $request->user());
        } catch (OfferException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['message' => 'Offre annulée.']);
    }
}
