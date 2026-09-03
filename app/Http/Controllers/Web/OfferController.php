<?php

namespace App\Http\Controllers\Web;

use App\Exceptions\OfferException;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Offer;
use App\Services\Offer\OfferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OfferController extends Controller
{
    public function __construct(private OfferService $offers) {}

    /**
     * Mes offres (en tant qu'acheteur) / offres reçues (en tant que
     * vendeur), regroupées par fil de négociation (annonce + acheteur).
     */
    public function index()
    {
        $user = Auth::user();

        $threads = Offer::where('buyer_id', $user->id)
            ->orWhere('seller_id', $user->id)
            ->with(['article.images', 'buyer', 'seller'])
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn (Offer $o) => $o->article_id . '-' . $o->buyer_id);

        return view('profile.offers', compact('threads'));
    }

    public function store(Request $request, Article $article)
    {
        $request->validate(['montant' => 'required|integer|min:1']);

        try {
            $this->offers->make(Auth::user(), $article, (int) $request->montant);
            return back()->with('success', 'Votre offre a été envoyée au vendeur.');
        } catch (OfferException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function counter(Request $request, Offer $offer)
    {
        $request->validate(['montant' => 'required|integer|min:1']);

        try {
            $this->offers->counter($offer, Auth::user(), (int) $request->montant);
            return back()->with('success', 'Votre contre-offre a été envoyée.');
        } catch (OfferException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function accept(Offer $offer)
    {
        try {
            $order = $this->offers->accept($offer, Auth::user());
        } catch (OfferException $e) {
            return back()->with('error', $e->getMessage());
        }

        if ($order->buyer_id === Auth::id()) {
            return redirect()->route('payment.show', $order)->with('success', 'Offre acceptée ! Vous pouvez procéder au paiement.');
        }

        return back()->with('success', 'Offre acceptée. L\'acheteur peut maintenant procéder au paiement.');
    }

    public function reject(Offer $offer)
    {
        try {
            $this->offers->reject($offer, Auth::user());
            return back()->with('success', 'Offre refusée.');
        } catch (OfferException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Offer $offer)
    {
        try {
            $this->offers->cancel($offer, Auth::user());
            return back()->with('success', 'Offre annulée.');
        } catch (OfferException $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
