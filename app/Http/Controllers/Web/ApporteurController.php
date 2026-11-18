<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\ApporteurException;
use App\Http\Controllers\Controller;
use App\Services\Apporteur\ApporteurService;
use Illuminate\Http\Request;

/** Parrainage : lien court public, et espace « Apporteur » du profil (code, lien, gains). */
class ApporteurController extends Controller
{
    public const SESSION_KEY = 'referral_code';

    public function __construct(private ApporteurService $apporteurs) {}

    /**
     * Lien partagé /parrainage/CODE : mémorise le code (même si le visiteur se promène avant
     * de s'inscrire) et ouvre l'inscription pré-remplie. Un code inconnu est simplement ignoré.
     */
    public function link(Request $request, string $code)
    {
        $apporteur = $this->apporteurs->findActiveByCode($code);
        if (! $apporteur) {
            return redirect()->route('register');
        }

        $request->session()->put(self::SESSION_KEY, $apporteur->code);

        return redirect()->route('register', ['ref' => $apporteur->code]);
    }

    public function show(Request $request)
    {
        $user = $request->user();

        try {
            $apporteur = $this->apporteurs->activate($user);
        } catch (ApporteurException) {
            $apporteur = null; // administrateur
        }

        return view('profile.apporteur', [
            'apporteur' => $apporteur,
            'shareLink' => $apporteur?->isActive() ? ApporteurService::shareLink($apporteur->code) : null,
            'stats' => $apporteur?->isActive() ? $this->apporteurs->stats($apporteur) : null,
            'gains' => $apporteur?->isActive()
                ? $apporteur->commissions()->with('seller:id,name')->latest('id')->limit(20)->get()
                : collect(),
            'referrer' => $user->referrer()->with('user')->first()?->user,
            'sharePercent' => (int) round(config('marketplace.apporteur_share') * 100),
        ]);
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
