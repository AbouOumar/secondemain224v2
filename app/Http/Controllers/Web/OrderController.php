<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Order;
use App\Services\Escrow\EscrowService;
use App\Services\Order\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function __construct(
        private EscrowService $escrow,
        private OrderService $orders,
    ) {}

    public function create(Request $request, $articleId, $delivery)
    {
        // Validate the article exists
        $article = Article::findOrFail($articleId);

        if ($article->statut === 'vendu') {
            return back()->with('error', 'Cet article a déjà été vendu.');
        }

        $order = $this->orders->createForArticle(Auth::user(), $article, (int) $delivery === 1);

        return redirect()->route('payment.show', $order);
    }

    /**
     * Confirmation de réception par l'acheteur pour une commande sans
     * livraison (remise en main propre) : déclenche la libération de
     * l'escrow au vendeur.
     */
    public function confirmReceipt(Order $order)
    {
        $this->authorize('confirmReceipt', $order);

        $order->update(['status' => 'livre']);

        if ($order->escrow) {
            $this->escrow->release($order->escrow, 'confirmation_reception');
        }

        return back()->with('success', 'Réception confirmée. Merci !');
    }
}
