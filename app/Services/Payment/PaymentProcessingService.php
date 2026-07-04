<?php
namespace App\Services\Payment;

use App\Models\Payment;

class PaymentProcessingService
{
    public function confirmPayment(Payment $payment): void
    {
        $order = $payment->order;

        if ($order->status === 'paye') {
            return;
        }

        $order->update(['status' => 'paye']);

        $article = $order->article;
        $article->decrement('stock');

        if ($article->stock == 0) {
            $article->update(['statut' => 'vendu']);
        }
    }
}
