<?php

namespace App\Services\Escrow;

use App\Models\Escrow;
use App\Models\Order;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

/**
 * Séquestre les fonds d'une commande jusqu'à confirmation de réception par
 * l'acheteur, puis les répartit entre le vendeur, le livreur et la
 * plateforme (commission).
 */
class EscrowService
{
    /**
     * Place les fonds d'une commande payée sous séquestre. Idempotent :
     * si l'escrow existe déjà pour cette commande, il est simplement retourné.
     */
    public function hold(Order $order): Escrow
    {
        $existing = $order->escrow ?? Escrow::where('order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        $commissionRate = (float) config('marketplace.commission_rate', 0.0);
        $commission = (int) round($order->prix_article * $commissionRate);
        $sellerAmount = (int) $order->prix_article - $commission;
        $riderAmount = (int) ($order->with_delivery ? $order->delivery_prix : 0);

        return Escrow::create([
            'order_id' => $order->id,
            'amount' => $order->prix_article,
            'commission_amount' => $commission,
            'seller_amount' => $sellerAmount,
            'rider_amount' => $riderAmount,
            'status' => 'retenu',
            'held_at' => now(),
        ]);
    }

    /**
     * Libère les fonds séquestrés : crédite le portefeuille du vendeur (et
     * celui du livreur si applicable). Idempotent.
     */
    public function release(Escrow $escrow, string $reason): void
    {
        if (in_array($escrow->status->value, ['libere', 'rembourse'], true)) {
            return;
        }

        DB::transaction(function () use ($escrow, $reason) {
            $order = $escrow->order()->lockForUpdate()->first()?->load('delivery');

            $this->credit(
                userId: $order->seller_id,
                montant: $escrow->seller_amount,
                source: 'vente',
                sourceId: $order->id,
                description: "Vente article — commande {$order->reference}",
            );

            if ($escrow->rider_amount > 0 && $order->delivery && $order->delivery->rider_id) {
                $this->credit(
                    userId: $order->delivery->rider_id,
                    montant: $escrow->rider_amount,
                    source: 'livraison',
                    sourceId: $order->id,
                    description: "Frais de livraison — commande {$order->reference}",
                );
            }

            $escrow->update([
                'status' => 'libere',
                'released_at' => now(),
                'release_reason' => $reason,
            ]);
        });
    }

    /**
     * Rembourse l'acheteur (crédit sur son portefeuille in-app) en cas
     * d'annulation ou de litige tranché en sa faveur. Idempotent.
     */
    public function refund(Escrow $escrow, string $reason): void
    {
        if (in_array($escrow->status->value, ['libere', 'rembourse'], true)) {
            return;
        }

        DB::transaction(function () use ($escrow, $reason) {
            $order = $escrow->order()->lockForUpdate()->first();

            $this->credit(
                userId: $order->buyer_id,
                montant: $order->total,
                source: 'remboursement',
                sourceId: $order->id,
                description: "Remboursement — commande {$order->reference}",
            );

            $escrow->update([
                'status' => 'rembourse',
                'refunded_at' => now(),
                'release_reason' => $reason,
            ]);
        });
    }

    /**
     * Marque l'escrow en litige : bloque la libération automatique tant
     * qu'un admin n'a pas tranché (via release/refund manuel).
     */
    public function markDisputed(Escrow $escrow): void
    {
        if (in_array($escrow->status->value, ['libere', 'rembourse'], true)) {
            return;
        }

        $escrow->update(['status' => 'litige']);
    }

    private function credit(int $userId, int $montant, string $source, int $sourceId, string $description): void
    {
        if ($montant <= 0) {
            return;
        }

        $wallet = Wallet::firstOrCreate(['user_id' => $userId], ['balance' => 0, 'currency' => 'GNF']);
        $wallet->increment('balance', $montant);

        Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'montant' => $montant,
            'reference' => 'TRX-' . strtoupper(\Illuminate\Support\Str::random(10)),
            'source' => $source,
            'source_id' => $sourceId,
            'description' => $description,
        ]);
    }
}
