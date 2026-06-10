<?php
namespace App\Services\Article;

use App\Models\Article;
use App\Models\Boost;
use App\Models\Wallet;
use App\Enums\TransactionType;
use App\Enums\TransactionSource;
use Illuminate\Support\Str;

class BoostService {
    const PRIX_PAR_HEURE = 500;

    public function boost(Article $article, int $dureeHeures = 24, ?int $paymentId = null): Boost
    {
        $total = $dureeHeures * self::PRIX_PAR_HEURE;

        if (!$paymentId) {
            $wallet = Wallet::firstOrCreate(
                ['user_id' => $article->user_id],
                ['balance' => 0, 'currency' => 'GNF']
            );

            if ($wallet->balance < $total) {
                throw new \RuntimeException('Solde insuffisant. Rechargez votre portefeuille.');
            }

            $wallet->decrement('balance', $total);

            $wallet->transactions()->create([
                'type' => TransactionType::Debit,
                'montant' => $total,
                'reference' => 'BOOST-' . strtoupper(Str::random(10)),
                'source' => TransactionSource::Boost,
                'source_id' => null,
                'description' => "Boost annonce #{$article->id} - {$dureeHeures}h",
            ]);
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
