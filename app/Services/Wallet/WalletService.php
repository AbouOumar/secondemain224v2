<?php

namespace App\Services\Wallet;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Mouvements du portefeuille. Chaque opération verrouille la ligne du portefeuille
 * pendant la transaction : deux opérations simultanées (retrait + Boost, notification
 * rejouée…) ne peuvent pas lire le même solde.
 */
class WalletService
{
    public function credit(int $userId, int $montant, string $source, ?int $sourceId, string $description): Transaction
    {
        return DB::transaction(function () use ($userId, $montant, $source, $sourceId, $description) {
            $wallet = $this->lockedWallet($userId);
            $wallet->increment('balance', $montant);

            return $this->record($wallet, 'credit', $montant, $source, $sourceId, $description);
        });
    }

    /**
     * @throws InsufficientFundsException si le solde ne couvre pas le montant
     */
    public function debit(int $userId, int $montant, string $source, ?int $sourceId, string $description): Transaction
    {
        return DB::transaction(function () use ($userId, $montant, $source, $sourceId, $description) {
            $wallet = $this->lockedWallet($userId);
            if ((float) $wallet->balance < $montant) {
                throw new InsufficientFundsException('Solde insuffisant.');
            }
            $wallet->decrement('balance', $montant);

            return $this->record($wallet, 'debit', $montant, $source, $sourceId, $description);
        });
    }

    private function lockedWallet(int $userId): Wallet
    {
        Wallet::firstOrCreate(['user_id' => $userId], ['balance' => 0, 'currency' => 'GNF']);

        return Wallet::where('user_id', $userId)->lockForUpdate()->firstOrFail();
    }

    private function record(Wallet $wallet, string $type, int $montant, string $source, ?int $sourceId, string $description): Transaction
    {
        return Transaction::create([
            'wallet_id' => $wallet->id,
            'type' => $type,
            'montant' => $montant,
            'reference' => 'TRX-' . strtoupper(Str::random(10)),
            'source' => $source,
            'source_id' => $sourceId,
            'description' => $description,
        ]);
    }
}
