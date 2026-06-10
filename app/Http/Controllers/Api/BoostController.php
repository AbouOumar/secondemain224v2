<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\Article\BoostService;
use Illuminate\Http\Request;

class BoostController extends Controller
{
    public function __construct(private BoostService $boostService) {}

    public function store(Request $request, Article $article)
    {
        if ($article->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Vous n\'êtes pas le propriétaire de cette annonce.'], 403);
        }

        $request->validate([
            'duree_heures' => 'nullable|integer|min:1|max:720',
            'payment_id' => 'nullable|integer|exists:payments,id',
        ]);

        try {
            $boost = $this->boostService->boost(
                $article,
                $request->duree_heures ?? 24,
                $request->payment_id
            );

            return response()->json([
                'success' => true,
                'boost' => $boost,
                'boosted_until' => $boost->end_at,
                'prix' => $boost->prix_paye,
                'duree_heures' => $boost->duree_heures,
                'wallet_debit' => is_null($request->payment_id),
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 400);
        }
    }

    public function pricing(Request $request)
    {
        $duree = $request->query('heures', 24);
        return response()->json([
            'prix_par_heure' => BoostService::PRIX_PAR_HEURE,
            'heures' => (int) $duree,
            'total' => $this->boostService->getPrix((int) $duree),
            'devise' => 'GNF',
        ]);
    }
}
