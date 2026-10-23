<?php
namespace App\Services\Payment;

use App\Models\Payment;
use App\Services\Escrow\EscrowService;
use App\Services\Notification\FirebaseNotificationService;

class PaymentProcessingService
{
    public function __construct(private EscrowService $escrow, private FirebaseNotificationService $notif) {}

    public function confirmPayment(Payment $payment): void
    {
        $order = $payment->order;

        if ($order->status->value === 'paye') {
            return;
        }

        $order->update(['status' => 'paye']);

        $article = $order->article;
        $article->decrement('stock');

        if ($article->stock == 0) {
            $article->update(['statut' => 'vendu']);
        }

        // Les fonds sont bloqués (escrow) jusqu'à confirmation de réception
        // par l'acheteur — voir EscrowService::release().
        $this->escrow->hold($order);

        $data = ['order_id' => $order->id, 'order_reference' => $order->reference, 'article_id' => $article->id];
        $this->notif->sendQuietly($order->buyer, 'Paiement confirmé', "Votre paiement pour \"{$article->titre}\" est confirmé. Les fonds sont bloqués jusqu'à la réception.", 'paiement.confirme', $data);
        $this->notif->sendQuietly($order->seller, 'Paiement reçu', "L'acheteur a payé \"{$article->titre}\". Préparez la remise de l'article.", 'paiement.recu', $data);
        if ($article->stock == 0) {
            $this->notif->sendQuietly($order->seller, 'Annonce vendue', "Votre annonce \"{$article->titre}\" est vendue.", 'annonce.vendue', ['article_id' => $article->id]);
        }
    }
}
