<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApporteurException;
use App\Http\Controllers\Controller;
use App\Models\Apporteur;
use App\Services\Apporteur\ApporteurService;
use Illuminate\Http\Request;

/** Programme apporteur d'affaires, côté app : candidature, code, gains, saisie d'un code. */
class ApporteurController extends Controller
{
    public function __construct(private ApporteurService $apporteurs) {}

    /**
     * État du compte dans le programme : sa candidature ou son code et ses gains,
     * et l'apporteur qui l'a recruté.
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $apporteur = $user->apporteurProfile()->first();

        return response()->json(['data' => [
            'apporteur' => $apporteur ? $this->format($apporteur, withStats: $apporteur->isActive()) : null,
            'referred_by' => $user->referrer?->user?->name,
            'can_enter_code' => $user->apporteur_id === null,
            'share_percent' => (int) round(config('marketplace.apporteur_share') * 100),
        ]]);
    }

    public function store(Request $request)
    {
        $details = $request->validate(ApporteurService::detailRules());

        try {
            $apporteur = $this->apporteurs->apply($request->user(), $details);
        } catch (ApporteurException $e) {
            return response()->json(['message' => $e->getMessage()], $e->status);
        }

        return response()->json(['data' => $this->format($apporteur)], 201);
    }

    public function destroy(Request $request)
    {
        $this->apporteurs->withdraw($request->user());

        return response()->json(null, 204);
    }

    /** Un vendeur déjà inscrit saisit le code de l'apporteur qui l'accompagne. */
    public function attach(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:20']);

        try {
            $apporteur = $this->apporteurs->attachReferral($request->user(), $data['code']);
        } catch (ApporteurException $e) {
            return response()->json(['message' => $e->getMessage(), 'errors' => ['code' => [$e->getMessage()]]], $e->status);
        }

        return response()->json(['message' => "Votre compte est relié à l'apporteur {$apporteur->user->name}."]);
    }

    private function format(Apporteur $apporteur, bool $withStats = false): array
    {
        $data = [
            'id' => $apporteur->id,
            'status' => $apporteur->status,
            'status_label' => $apporteur->statusLabel(),
            'code' => $apporteur->isActive() ? $apporteur->code : null,
            'share_link' => $apporteur->isActive() ? route('register', ['ref' => $apporteur->code]) : null,
            'details' => $apporteur->details,
            'note' => $apporteur->note,
            'created_at' => $apporteur->created_at,
            'processed_at' => $apporteur->processed_at,
        ];

        if ($withStats) {
            $data['stats'] = $this->apporteurs->stats($apporteur);
            $data['recent_gains'] = $apporteur->commissions()->with('seller:id,name')->latest('id')->limit(20)->get()
                ->map(fn ($c) => [
                    'seller' => $c->seller?->name,
                    'amount' => $c->amount,
                    'status' => $c->status,
                    'date' => $c->created_at,
                ]);
        }

        return $data;
    }
}
