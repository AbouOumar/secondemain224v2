<?php

namespace App\Services\Account;

use App\Enums\UserStatus;
use App\Models\Delivery;
use App\Models\IdentityVerification;
use App\Models\Offer;
use App\Models\Order;
use App\Models\User;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Suppression d'un compte à la demande de son titulaire.
 *
 * Le compte est anonymisé plutôt que supprimé : les commandes, paiements et
 * avis terminés restent liés à une ligne « Compte supprimé » (obligations
 * comptables, historique de l'autre partie). Tout ce qui est personnel disparaît.
 */
class AccountDeletionService
{
    /** Commandes dont l'argent est en séquestre ou la livraison en cours. */
    private const OPEN_ORDER_STATUSES = ['paye', 'en_cours'];

    private const ACTIVE_DELIVERY_STATUSES = ['acceptee', 'en_cours', 'livree'];

    /**
     * Raisons pour lesquelles le compte ne peut pas être supprimé maintenant
     * (liste vide : suppression possible).
     *
     * @return string[]
     */
    public function blockers(User $user): array
    {
        $blockers = [];

        $hasOpenOrder = Order::whereIn('status', self::OPEN_ORDER_STATUSES)
            ->where(fn ($q) => $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id))
            ->exists();
        if ($hasOpenOrder) {
            $blockers[] = 'Une commande payée est en cours : terminez-la (réception confirmée) avant de supprimer votre compte.';
        }

        if (Delivery::where('rider_id', $user->id)->whereIn('status', self::ACTIVE_DELIVERY_STATUSES)->exists()) {
            $blockers[] = 'Une livraison est en cours : terminez-la avant de supprimer votre compte.';
        }

        if (WithdrawalRequest::where('user_id', $user->id)->where('status', WithdrawalRequest::PENDING)->exists()) {
            $blockers[] = 'Un retrait est en cours de traitement : attendez qu\'il soit terminé avant de supprimer votre compte.';
        }

        $balance = (float) ($user->wallet?->balance ?? 0);
        if ($balance > 0) {
            $blockers[] = "Votre portefeuille contient de l'argent : contactez le support pour le récupérer avant de supprimer votre compte.";
        }

        return $blockers;
    }

    /** L'utilisateur s'est connecté avec Google : il n'a pas de mot de passe à confirmer. */
    public function needsPassword(User $user): bool
    {
        return ! $user->oauthProviders()->exists();
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            // Ce qui n'a pas abouti est annulé, pour ne rien laisser en attente chez l'autre partie.
            Order::where('status', 'en_attente_paiement')
                ->where(fn ($q) => $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id))
                ->update(['status' => 'annule', 'annule_raison' => 'compte_supprime']);
            Offer::where('status', 'en_attente')
                ->where(fn ($q) => $q->where('buyer_id', $user->id)->orWhere('seller_id', $user->id))
                ->update(['status' => 'annulee']);

            $user->articles()->update(['is_published' => false]);

            // Données personnelles liées au compte.
            $user->deviceTokens()->delete();
            $user->oauthProviders()->delete();
            $user->searchAlerts()->delete();
            $user->notifications()->delete();
            $user->savedArticles()->detach();
            $user->subscriptions()->delete();
            $user->tokens()->delete();

            $partner = $user->partner;
            if ($partner) {
                foreach ([$partner->logo, $partner->couverture] as $path) {
                    if ($path) {
                        Storage::disk('public')->delete($path);
                    }
                }
                $partner->delete();
            }

            foreach ($user->identityVerifications as $verification) {
                foreach ([$verification->document_path, $verification->selfie_path] as $path) {
                    if ($path) {
                        Storage::disk(IdentityVerification::DISK)->delete($path);
                    }
                }
                $verification->delete();
            }

            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            // Le compte reste (clés étrangères), vidé de toute information personnelle.
            $user->forceFill([
                'name' => 'Compte supprimé',
                'email' => null,
                'email_verified_at' => null,
                'phone' => 'deleted-' . $user->id,
                'phone_verified_at' => null,
                'password' => Hash::make(Str::random(64)),
                'remember_token' => null,
                'avatar' => null,
                'latitude' => null,
                'longitude' => null,
                'status' => UserStatus::Suspendu,
                'is_verified' => false,
                'verified_at' => null,
                'verification_documents' => null,
                'rider_status' => 'hors_ligne',
                'email_preferences' => null,
                'newsletter_subscribed_at' => null,
            ])->save();
        });
    }
}
