<?php
namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRatingRequest;
use App\Models\Order;
use App\Models\Rating;
use App\Models\User;
use App\Http\Resources\RatingResource;

class RatingController extends Controller
{
    /**
     * Un avis se donne à l'issue d'une commande livrée, entre ses parties :
     * l'acheteur note le vendeur (ou le livreur), le vendeur note l'acheteur.
     * Un seul avis par commande, par auteur et par rôle noté.
     */
    public function store(StoreRatingRequest $request) {
        $user = $request->user();
        $order = Order::with('delivery')->findOrFail($request->order_id);

        $isBuyer = $order->buyer_id === $user->id;
        $isSeller = $order->seller_id === $user->id;
        if (! $isBuyer && ! $isSeller) {
            abort(403, "Vous ne participez pas à cette commande.");
        }

        if ($order->status->value !== 'livre') {
            return response()->json(['message' => 'Vous pourrez donner votre avis une fois la commande livrée.'], 422);
        }

        // Qui peut être noté, et sous quel rôle.
        $allowed = $isBuyer
            ? ['vendeur' => $order->seller_id, 'motard' => $order->delivery?->rider_id]
            : ['acheteur' => $order->buyer_id];
        if (($allowed[$request->role_type] ?? null) !== (int) $request->rated_id) {
            return response()->json(['message' => "Cet avis ne correspond pas à une personne de cette commande."], 422);
        }

        $alreadyRated = Rating::where('rater_id', $user->id)
            ->where('order_id', $order->id)
            ->where('role_type', $request->role_type)
            ->exists();
        if ($alreadyRated) {
            return response()->json(['message' => 'Vous avez déjà donné votre avis pour cette commande.'], 409);
        }

        $rating = Rating::create([
            'rater_id' => $user->id,
            'rated_id' => $request->rated_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'role_type' => $request->role_type,
            'order_id' => $order->id,
        ]);
        $rating->load('rater');
        return new RatingResource($rating, 201);
    }

    public function userRatings(User $user) {
        $ratings = Rating::where('rated_id', $user->id)
            ->with('rater')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('role_type');
        return response()->json([
            'data' => $ratings->map(function($items, $roleType) {
                return [
                    'role_type' => $roleType,
                    'average' => $items->avg('rating'),
                    'count' => $items->count(),
                    'ratings' => RatingResource::collection($items),
                ];
            })->values(),
        ]);
    }
}
