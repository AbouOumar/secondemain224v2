<?php

namespace App\Console\Commands;

use App\Models\Escrow;
use App\Services\Escrow\EscrowService;
use Illuminate\Console\Command;

class ReleaseEscrowFunds extends Command
{
    protected $signature = 'escrow:auto-release';
    protected $description = "Libère automatiquement les escrows dont le délai de confirmation acheteur est dépassé (filet de sécurité)";

    public function handle(EscrowService $escrow): void
    {
        $delivredHours = (int) config('marketplace.escrow_auto_release_delivered_hours', 72);
        $noDeliveryDays = (int) config('marketplace.escrow_auto_release_no_delivery_days', 7);

        $candidates = Escrow::where('status', 'retenu')
            ->with(['order.delivery'])
            ->get()
            ->filter(function (Escrow $e) use ($delivredHours, $noDeliveryDays) {
                $order = $e->order;
                if (! $order) {
                    return false;
                }

                if ($order->with_delivery) {
                    $delivery = $order->delivery;
                    return $delivery
                        && $delivery->status->value === 'effectuee'
                        && $delivery->completed_at
                        && $delivery->completed_at->lte(now()->subHours($delivredHours));
                }

                return $order->status->value === 'paye'
                    && $order->updated_at->lte(now()->subDays($noDeliveryDays));
            });

        $count = 0;
        foreach ($candidates as $e) {
            $escrow->release($e, 'auto_release_delai');
            $count++;
        }

        $this->info("{$count} escrow(s) libéré(s) automatiquement.");
    }
}
