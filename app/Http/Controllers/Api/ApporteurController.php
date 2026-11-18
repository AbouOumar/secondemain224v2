<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApporteurException;
use App\Http\Controllers\Controller;
use App\Models\Apporteur;
use App\Services\Apporteur\ApporteurService;
use Illuminate\Http\Request;

/** Parrainage côté app : son code et son lien, ses gains, et la saisie du code de son parrain. */
class ApporteurController extends Controller
{
    public function __construct(private ApporteurService $apporteurs) {}

    /**
     * Code de parrainage du compte (créé à la première ouverture, sans validation), gains,
     * et apporteur qui a recruté ce compte. Un administrateur n'a pas de code.
     */
    public function show(Request $request)
    {
        $user = $request->user();

        try {
            $apporteur = $this->apporteurs->activate($user);
        } catch (ApporteurException) {
            $apporteur = null;
        }

        return response()->json(['data' => [
            'apporteur' => $apporteur ? $this->format($apporteur) : null,
            'referred_by' => $user->referrer()->with('user:id,name')->first()?->user?->name,
            'can_enter_code' => $user->apporteur_id === null,
            'share_percent' => (int) round(config('marketplace.apporteur_share') * 100),
        ]]);
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

    private function format(Apporteur $apporteur): array
    {
        $active = $apporteur->isActive();

        $data = [
            'id' => $apporteur->id,
            'status' => $apporteur->status,
            'status_label' => $apporteur->statusLabel(),
            'code' => $active ? $apporteur->code : null,
            'share_link' => $active ? ApporteurService::shareLink($apporteur->code) : null,
            'note' => $apporteur->note,
            'created_at' => $apporteur->created_at,
        ];

        if ($active) {
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
