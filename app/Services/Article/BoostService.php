<?php
namespace App\Services\Article;

use App\Models\Article;
use App\Models\Boost;
use App\Services\Wallet\InsufficientFundsException;
use App\Services\Wallet\WalletService;
use App\Enums\TransactionType;
use App\Enums\TransactionSource;
use Illuminate\Support\Str;

class BoostService {
    const PRIX_PAR_HEURE = 500;

    public function boost(Article $article, int $dureeHeures = 24, ?int $paymentId = null): Boost
    {
        $total = $dureeHeures * self::PRIX_PAR_HEURE;

        if (!$paymentId) {
            // Débit sous verrou : deux Boosts (ou un retrait) simultanés ne peuvent pas dépasser le solde.
            try {
                app(WalletService::class)->debit(
                    $article->user_id,
                    $total,
                    'boost',
                    null,
                    "Boost annonce #{$article->id} - {$dureeHeures}h",
                );
            } catch (InsufficientFundsException) {
                throw new \RuntimeException('Solde insuffisant. Rechargez votre portefeuille.');
            }
        }

        $boost = Boost::create([
            'article_id' => $article->id,
            'user_id' => $article->user_id,
            'prix_paye' => $total,
            'duree_heures' => $dureeHeures,
            'start_at' => now(),
            'end_at' => now()->addHours($dureeHeures),
            'payment_id' => $paymentId,
        ]);

        $article->update([
            'is_boosted' => true,
            'boosted_until' => $boost->end_at,
        ]);

        return $boost;
    }

    public function expireBoost(Article $article): void
    {
        $article->update([
            'is_boosted' => false,
            'boosted_until' => null,
        ]);
    }

    public function getPrix(int $dureeHeures): int
    {
        return $dureeHeures * self::PRIX_PAR_HEURE;
    }
}
