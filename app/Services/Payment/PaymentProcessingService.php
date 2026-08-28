<?php
namespace App\Services\Payment;

use App\Models\Payment;
use App\Services\Escrow\EscrowService;

class PaymentProcessingService
{
    public function __construct(private EscrowService $escrow) {}

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
    }
}
