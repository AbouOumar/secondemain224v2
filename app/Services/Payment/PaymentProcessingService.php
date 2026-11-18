<?php
namespace App\Services\Payment;

use App\Models\Article;
use App\Models\Order;
use App\Models\Payment;
use App\Services\Escrow\EscrowService;
use App\Services\Notification\FirebaseNotificationService;
use Illuminate\Support\Facades\DB;

class PaymentProcessingService
{
    public function __construct(private EscrowService $escrow, private FirebaseNotificationService $notif) {}

    public function confirmPayment(Payment $payment): void
    {
        // Commande et annonce sont verrouillées : deux confirmations simultanées (webhook rejoué,
        // deux acheteurs sur la dernière pièce) ne peuvent ni décrémenter deux fois ni passer sous 0.
        $confirmed = DB::transaction(function () use ($payment) {
            $order = Order::whereKey($payment->order_id)->lockForUpdate()->firstOrFail();

            if ($order->status->value === 'paye') {
                return null;
            }

            $order->update(['status' => 'paye']);

            $article = Article::whereKey($order->article_id)->lockForUpdate()->firstOrFail();
            // Stock vide (null) = annonce à pièce unique.
            $stock = max(0, (int) ($article->stock ?? 1) - 1);
            $article->update(['stock' => $stock] + ($stock === 0 ? ['statut' => 'vendu'] : []));

            // Les fonds sont bloqués (escrow) jusqu'à confirmation de réception
            // par l'acheteur — voir EscrowService::release().
            $this->escrow->hold($order);

            return [$order, $article];
        });

        if (! $confirmed) {
            return;
        }

        [$order, $article] = $confirmed;

        $data = ['order_id' => $order->id, 'order_reference' => $order->reference, 'article_id' => $article->id];
        $this->notif->sendQuietly($order->buyer, 'Paiement confirmé', "Votre paiement pour \"{$article->titre}\" est confirmé. Les fonds sont bloqués jusqu'à la réception.", 'paiement.confirme', $data);
        $this->notif->sendQuietly($order->seller, 'Paiement reçu', "L'acheteur a payé \"{$article->titre}\". Préparez la remise de l'article.", 'paiement.recu', $data);
        if ($article->stock === 0) {
            $this->notif->sendQuietly($order->seller, 'Annonce vendue', "Votre annonce \"{$article->titre}\" est vendue.", 'annonce.vendue', ['article_id' => $article->id]);
        }
    }
}
