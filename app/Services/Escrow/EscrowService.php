<?php

namespace App\Services\Escrow;

use App\Models\Escrow;
use App\Models\Order;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

/**
 * Séquestre les fonds d'une commande jusqu'à confirmation de réception par
 * l'acheteur, puis les répartit entre le vendeur, le livreur et la
 * plateforme (commission).
 */
class EscrowService
{
    public function __construct(private WalletService $wallet) {}

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
     * celui du livreur si applicable). Idempotent, y compris en cas d'appels simultanés
     * (le séquestre est verrouillé puis relu dans la transaction).
     */
    public function release(Escrow $escrow, string $reason): void
    {
        DB::transaction(function () use ($escrow, $reason) {
            $locked = $this->lockOpen($escrow);
            if (! $locked) {
                return;
            }

            $order = $locked->order()->lockForUpdate()->first()?->load('delivery');

            $this->credit(
                userId: $order->seller_id,
                montant: $locked->seller_amount,
                source: 'vente',
                sourceId: $order->id,
                description: "Vente article — commande {$order->reference}",
            );

            if ($locked->rider_amount > 0 && $order->delivery && $order->delivery->rider_id) {
                $this->credit(
                    userId: $order->delivery->rider_id,
                    montant: $locked->rider_amount,
                    source: 'livraison',
                    sourceId: $order->id,
                    description: "Frais de livraison — commande {$order->reference}",
                );
            }

            $locked->update([
                'status' => 'libere',
                'released_at' => now(),
                'release_reason' => $reason,
            ]);
            $escrow->setRawAttributes($locked->getAttributes(), true);
        });
    }

    /**
     * Rembourse l'acheteur (crédit sur son portefeuille in-app) en cas
     * d'annulation ou de litige tranché en sa faveur. Idempotent, même en cas d'appels simultanés.
     */
    public function refund(Escrow $escrow, string $reason): void
    {
        DB::transaction(function () use ($escrow, $reason) {
            $locked = $this->lockOpen($escrow);
            if (! $locked) {
                return;
            }

            $order = $locked->order()->lockForUpdate()->first();

            $this->credit(
                userId: $order->buyer_id,
                montant: $order->total,
                source: 'remboursement',
                sourceId: $order->id,
                description: "Remboursement — commande {$order->reference}",
            );

            $locked->update([
                'status' => 'rembourse',
                'refunded_at' => now(),
                'release_reason' => $reason,
            ]);
            $escrow->setRawAttributes($locked->getAttributes(), true);
        });
    }

    /**
     * Marque l'escrow en litige : bloque la libération automatique tant
     * qu'un admin n'a pas tranché (via release/refund manuel).
     */
    public function markDisputed(Escrow $escrow): void
    {
        DB::transaction(function () use ($escrow) {
            $locked = $this->lockOpen($escrow);
            if (! $locked) {
                return;
            }

            $locked->update(['status' => 'litige']);
            $escrow->setRawAttributes($locked->getAttributes(), true);
        });
    }

    /** Verrouille la ligne du séquestre ; null s'il est déjà libéré ou remboursé. */
    private function lockOpen(Escrow $escrow): ?Escrow
    {
        $locked = Escrow::whereKey($escrow->id)->lockForUpdate()->firstOrFail();

        return in_array($locked->status->value, ['libere', 'rembourse'], true) ? null : $locked;
    }

    private function credit(int $userId, int $montant, string $source, int $sourceId, string $description): void
    {
        if ($montant <= 0) {
            return;
        }

        $this->wallet->credit($userId, $montant, $source, $sourceId, $description);
    }
}
