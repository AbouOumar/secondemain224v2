<?php

namespace App\Services\Seller;

use App\Models\Message;
use App\Models\Order;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Calcule les statistiques publiques d'un vendeur (nb de ventes, taux de
 * réponse, ancienneté, note moyenne) affichées sur son profil public.
 * Résultats mis en cache car certains calculs (taux de réponse) parcourent
 * les conversations et ne doivent pas être recalculés à chaque vue.
 */
class SellerStatsService
{
    public function getStats(User $seller): array
    {
        return Cache::remember("seller_stats_{$seller->id}", 900, function () use ($seller) {
            return [
                'sales_count' => $this->salesCount($seller),
                'member_since' => $seller->created_at,
                'rating_avg' => round((float) Rating::where('rated_id', $seller->id)->avg('rating'), 1),
                'rating_count' => Rating::where('rated_id', $seller->id)->count(),
                'response_rate' => $this->responseRate($seller),
                'is_verified' => (bool) $seller->is_verified,
                'verified_at' => $seller->verified_at,
            ];
        });
    }

    private function salesCount(User $seller): int
    {
        return Order::where('seller_id', $seller->id)
            ->whereHas('escrow', fn ($q) => $q->where('status', 'libere'))
            ->count();
    }

    /**
     * Pourcentage d'interlocuteurs ayant reçu une réponse ; null si personne n'a encore écrit.
     */
    private function responseRate(User $seller): ?int
    {
        $senderIds = Message::where('receiver_id', $seller->id)
            ->distinct()
            ->pluck('sender_id');

        if ($senderIds->isEmpty()) {
            return null;
        }

        $repliedTo = 0;
        foreach ($senderIds as $senderId) {
            $replied = Message::where('sender_id', $seller->id)
                ->where('receiver_id', $senderId)
                ->exists();
            if ($replied) {
                $repliedTo++;
            }
        }

        return (int) round(($repliedTo / $senderIds->count()) * 100);
    }
}
