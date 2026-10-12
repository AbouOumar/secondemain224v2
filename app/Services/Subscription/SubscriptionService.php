<?php

namespace App\Services\Subscription;

use App\Exceptions\SubscriptionException;
use App\Models\IdentityVerification;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Wallet\InsufficientFundsException;
use App\Services\Wallet\WalletService;
use Illuminate\Support\Facades\DB;

/**
 * Abonnements des revendeurs pro, payés avec le portefeuille. Un plan à badge donne le badge
 * « revendeur vérifié » tant qu'il est actif ; il le retire à la fin ou à l'annulation, sauf
 * si l'identité du vendeur a été vérifiée séparément.
 */
class SubscriptionService
{
    public function __construct(private WalletService $wallet) {}

    /** @return array<string, array<string, mixed>> */
    public function plans(): array
    {
        return config('subscriptions.plans');
    }

    /**
     * @throws SubscriptionException plan inconnu ou déjà actif (409), solde insuffisant (422)
     */
    public function subscribe(User $user, string $planKey): Subscription
    {
        $plan = $this->plans()[$planKey] ?? throw new SubscriptionException('Plan inconnu.', 422);
        $price = (int) $plan['price'];

        return DB::transaction(function () use ($user, $planKey, $plan, $price) {
            $current = Subscription::where('user_id', $user->id)->lockForUpdate()->first();
            if ($current && $current->isActive() && $current->plan_type === $planKey) {
                throw new SubscriptionException('Ce plan est déjà actif.', 409);
            }

            if ($price > 0) {
                try {
                    $this->wallet->debit($user->id, $price, 'abonnement', null, "Abonnement {$plan['label']}");
                } catch (InsufficientFundsException) {
                    throw new SubscriptionException('Solde insuffisant. Rechargez votre portefeuille.', 422);
                }
            }

            $subscription = Subscription::updateOrCreate(['user_id' => $user->id], [
                'plan_type' => $planKey,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => $price > 0 ? now()->addDays((int) config('subscriptions.duration_days')) : null,
                'provider' => 'wallet',
            ]);

            if ($plan['badge']) {
                $user->forceFill(['is_verified' => true, 'verified_at' => $user->verified_at ?? now()])->save();
            } else {
                $this->releaseBadge($user);
            }

            return $subscription;
        });
    }

    /** Annule l'abonnement tout de suite, sans remboursement. Faux s'il n'y en avait pas d'actif. */
    public function cancel(User $user): bool
    {
        $subscription = Subscription::where('user_id', $user->id)->first();
        if (! $subscription || ! $subscription->isActive()) {
            return false;
        }

        $subscription->update(['status' => 'cancelled', 'ends_at' => now()]);
        $this->releaseBadge($user);

        return true;
    }

    /** Passe en « expiré » les abonnements arrivés à terme et retire leur badge. Renvoie leur nombre. */
    public function expireDue(): int
    {
        $due = Subscription::where('status', 'active')->whereNotNull('ends_at')->where('ends_at', '<=', now())->with('user')->get();

        foreach ($due as $subscription) {
            $subscription->update(['status' => 'expired']);
            if ($subscription->user) {
                $this->releaseBadge($subscription->user);
            }
        }

        return $due->count();
    }

    /** Retire le badge, sauf si une vérification d'identité approuvée le justifie par ailleurs. */
    private function releaseBadge(User $user): void
    {
        $identityVerified = IdentityVerification::where('user_id', $user->id)->where('status', 'approved')->exists();
        if (! $identityVerified && $user->is_verified) {
            $user->forceFill(['is_verified' => false])->save();
        }
    }
}
