<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\ApporteurException;
use App\Http\Controllers\Controller;
use App\Services\Apporteur\ApporteurService;
use Illuminate\Http\Request;

/** Espace « apporteur d'affaires » du profil : candidature, code, gains, saisie d'un code. */
class ApporteurController extends Controller
{
    public function __construct(private ApporteurService $apporteurs) {}

    public function show(Request $request)
    {
        $user = $request->user();
        $apporteur = $user->apporteurProfile()->first();

        return view('profile.apporteur', [
            'apporteur' => $apporteur,
            'stats' => $apporteur?->isActive() ? $this->apporteurs->stats($apporteur) : null,
            'gains' => $apporteur?->isActive()
                ? $apporteur->commissions()->with('seller:id,name')->latest('id')->limit(20)->get()
                : collect(),
            'referrer' => $user->referrer?->user,
            'sharePercent' => (int) round(config('marketplace.apporteur_share') * 100),
        ]);
    }

    public function apply(Request $request)
    {
        $details = $request->validate(ApporteurService::detailRules(), [
            'quartier.required' => 'Indiquez votre quartier.',
        ]);

        try {
            $this->apporteurs->apply($request->user(), $details);
        } catch (ApporteurException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Candidature envoyée. Notre équipe vous contactera sur WhatsApp pour la formation.');
    }

    public function withdraw(Request $request)
    {
        $this->apporteurs->withdraw($request->user());

        return back()->with('success', 'Candidature retirée.');
    }

    public function attach(Request $request)
    {
        $data = $request->validate(['code' => 'required|string|max:20'], ['code.required' => 'Saisissez le code de votre apporteur.']);

        try {
            $apporteur = $this->apporteurs->attachReferral($request->user(), $data['code']);
        } catch (ApporteurException $e) {
            return back()->withErrors(['code' => $e->getMessage()])->withInput();
        }

        return back()->with('success', "Votre compte est relié à l'apporteur {$apporteur->user->name}.");
    }
}
