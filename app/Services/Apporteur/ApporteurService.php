<?php

namespace App\Services\Apporteur;

use App\Exceptions\ApporteurException;
use App\Models\Apporteur;
use App\Models\ApporteurCommission;
use App\Models\Escrow;
use App\Models\Order;
use App\Models\User;
use App\Services\Notification\FirebaseNotificationService;
use App\Services\Wallet\WalletService;
use Carbon\CarbonInterface;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Programme « apporteur d'affaires » :
 *  - candidature, examinée par l'administration, qui attribue un code personnel ;
 *  - un vendeur saisit ce code à l'inscription (ou plus tard, tant qu'il n'a rien vendu) ;
 *  - à la première vente de ce vendeur payée en ligne, l'apporteur reçoit une part de la
 *    commission de la plateforme dans son portefeuille, à la libération du séquestre.
 */
class ApporteurService
{
    /** Caractères des codes : sans 0/O ni 1/I/L, faciles à dicter et à recopier. */
    private const CODE_ALPHABET = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
    private const CODE_LENGTH = 6;

    public function __construct(
        private WalletService $wallet,
        private FirebaseNotificationService $notifications,
    ) {}

    public static function detailRules(): array
    {
        return [
            'quartier' => 'required|string|max:100',
            'activite' => 'nullable|string|max:100',
            'motivation' => 'nullable|string|max:500',
        ];
    }

    /**
     * @param array{quartier: string, activite?: ?string, motivation?: ?string} $details
     * @throws ApporteurException déjà apporteur (409), demande en cours (409), compte suspendu (403)
     */
    public function apply(User $user, array $details): Apporteur
    {
        $apporteur = DB::transaction(function () use ($user, $details) {
            $existing = Apporteur::where('user_id', $user->id)->lockForUpdate()->first();

            if ($existing?->status === Apporteur::ACTIVE) {
                throw new ApporteurException('Vous êtes déjà apporteur d\'affaires.', 409);
            }
            if ($existing?->status === Apporteur::PENDING) {
                throw new ApporteurException('Votre demande est déjà en cours d\'examen.', 409);
            }
            if ($existing?->status === Apporteur::SUSPENDED) {
                throw new ApporteurException('Votre accès apporteur est suspendu. Contactez l\'équipe.', 403);
            }

            // Nouvelle candidature, ou nouvelle tentative après un refus.
            $apporteur = $existing ?? new Apporteur(['user_id' => $user->id]);
            $apporteur->fill([
                'status' => Apporteur::PENDING,
                'details' => $details,
                'note' => null,
                'processed_by' => null,
                'processed_at' => null,
            ])->save();

            return $apporteur;
        });

        $this->notifications->sendToAdmins(
            'Nouvelle candidature apporteur',
            "{$user->name} veut devenir apporteur d'affaires ({$details['quartier']}).",
            'admin.apporteur_demande',
            ['apporteur_id' => $apporteur->id],
        );

        return $apporteur;
    }

    /** Le candidat retire sa demande en attente. */
    public function withdraw(User $user): void
    {
        Apporteur::where('user_id', $user->id)->where('status', Apporteur::PENDING)->delete();
    }

    /** Accepte la candidature et attribue un code. Faux si elle avait déjà été traitée. */
    public function approve(Apporteur $apporteur, User $admin): bool
    {
        $done = DB::transaction(function () use ($apporteur, $admin) {
            $locked = Apporteur::whereKey($apporteur->id)->lockForUpdate()->first();
            if (! $locked || $locked->status !== Apporteur::PENDING) {
                return false;
            }

            $locked->fill([
                'status' => Apporteur::ACTIVE,
                'code' => $locked->code ?? $this->uniqueCode(),
                'note' => null,
                'processed_by' => $admin->id,
                'processed_at' => now(),
            ])->save();
            $apporteur->setRawAttributes($locked->getAttributes(), true);

            return true;
        });

        if ($done) {
            $this->notifications->sendQuietly(
                $apporteur->user,
                'Vous êtes apporteur d\'affaires',
                "Bienvenue dans l'équipe ! Votre code est {$apporteur->code} : donnez-le aux vendeurs que vous inscrivez.",
                'apporteur_traite',
                ['status' => Apporteur::ACTIVE, 'code' => $apporteur->code],
            );
        }

        return $done;
    }

    /** Refuse la candidature. Faux si elle avait déjà été traitée. */
    public function reject(Apporteur $apporteur, User $admin, ?string $note): bool
    {
        $done = Apporteur::whereKey($apporteur->id)->where('status', Apporteur::PENDING)
            ->update(['status' => Apporteur::REJECTED, 'note' => $note, 'processed_by' => $admin->id, 'processed_at' => now()]) === 1;

        if ($done) {
            $this->notifications->sendQuietly(
                $apporteur->user,
                'Candidature apporteur',
                "Votre candidature d'apporteur d'affaires n'a pas été retenue" . ($note ? " : {$note}." : '.'),
                'apporteur_traite',
                ['status' => Apporteur::REJECTED],
            );
        }

        return $done;
    }

    /**
     * Suspend un apporteur (fraude, comportement envers les vendeurs) ou le réactive.
     * Suspendu, son code n'est plus accepté et ses gains ne sont plus versés.
     */
    public function setSuspended(Apporteur $apporteur, bool $suspended, User $admin, ?string $note = null): bool
    {
        [$from, $to] = $suspended ? [Apporteur::ACTIVE, Apporteur::SUSPENDED] : [Apporteur::SUSPENDED, Apporteur::ACTIVE];

        return Apporteur::whereKey($apporteur->id)->where('status', $from)
            ->update(['status' => $to, 'note' => $note, 'processed_by' => $admin->id, 'processed_at' => now()]) === 1;
    }

    /** Apporteur actif correspondant au code saisi (insensible à la casse et aux espaces). */
    public function findActiveByCode(?string $code): ?Apporteur
    {
        $code = strtoupper(preg_replace('/[\s-]+/', '', (string) $code));
        if ($code === '') {
            return null;
        }

        return Apporteur::where('code', $code)->where('status', Apporteur::ACTIVE)->first();
    }

    /**
     * Relie un compte à l'apporteur dont il a saisi le code.
     *
     * @throws ApporteurException code inconnu (422), propre code (422), déjà relié ou a déjà vendu (409)
     */
    public function attachReferral(User $user, string $code): Apporteur
    {
        $apporteur = $this->findActiveByCode($code);
        if (! $apporteur) {
            throw new ApporteurException('Ce code apporteur est inconnu ou n\'est plus actif.', 422);
        }
        if ($apporteur->user_id === $user->id) {
            throw new ApporteurException('Vous ne pouvez pas utiliser votre propre code.', 422);
        }

        $attached = DB::transaction(function () use ($user, $apporteur) {
            $locked = User::whereKey($user->id)->lockForUpdate()->first();
            if ($locked->apporteur_id !== null) {
                throw new ApporteurException('Votre compte est déjà relié à un apporteur.', 409);
            }
            // Un vendeur déjà actif n'a pas été « apporté » : pas de rattachement après une première vente.
            if (Order::where('seller_id', $user->id)->whereHas('escrow', fn ($q) => $q->where('status', 'libere'))->exists()) {
                throw new ApporteurException('Le code apporteur se saisit avant votre première vente.', 409);
            }

            $locked->forceFill(['apporteur_id' => $apporteur->id, 'referred_at' => now()])->save();

            return $locked;
        });
        $user->setRawAttributes($attached->getAttributes(), true);

        return $apporteur;
    }

    /**
     * À la libération du séquestre : si c'est la première vente d'un vendeur recruté,
     * verse sa part à l'apporteur. Appelé dans la transaction de libération ; idempotent
     * (une seule commission par vendeur, garantie aussi par un index unique).
     */
    public function rewardFirstSale(Order $order, Escrow $escrow): ?ApporteurCommission
    {
        $seller = User::find($order->seller_id);
        $apporteur = $seller?->referrer;
        if (! $apporteur || $apporteur->user_id === $seller->id) {
            return null;
        }
        if (ApporteurCommission::where('seller_id', $seller->id)->exists()) {
            return null; // ce n'est pas sa première vente
        }

        $share = (float) config('marketplace.apporteur_share', 0.30);
        $amount = (int) floor((int) $escrow->commission_amount * $share);
        $paid = $apporteur->isActive() && $amount > 0;

        try {
            // Point de sauvegarde : un doublon simultané ne doit pas annuler la libération du séquestre.
            $commission = DB::transaction(fn () => ApporteurCommission::create([
                'apporteur_id' => $apporteur->id,
                'seller_id' => $seller->id,
                'order_id' => $order->id,
                'commission_amount' => (int) $escrow->commission_amount,
                'share' => $share,
                'amount' => $amount,
                'status' => $paid ? ApporteurCommission::PAID : ApporteurCommission::NOT_PAID,
            ]));
        } catch (QueryException) {
            return null; // déjà enregistrée par un appel concurrent
        }

        if ($paid) {
            $transaction = $this->wallet->credit(
                $apporteur->user_id,
                $amount,
                'apport',
                $order->id,
                "Apport d'affaires — première vente de {$seller->name} (commande {$order->reference})",
            );
            $commission->update(['transaction_id' => $transaction->id]);

            DB::afterCommit(fn () => $this->notifications->sendQuietly(
                $apporteur->user,
                'Vous avez gagné '.number_format($amount, 0, ',', ' ').' GNF',
                "{$seller->name}, que vous avez apporté, a réalisé sa première vente. Votre part est dans votre portefeuille.",
                'apporteur_gain',
                ['order_id' => $order->id, 'amount' => $amount],
            ));
        }

        return $commission;
    }

    /**
     * Statistiques d'un apporteur pour son tableau de bord.
     *
     * @return array{sellers: int, active_sellers: int, first_sales: int, earned: int}
     */
    public function stats(Apporteur $apporteur): array
    {
        return [
            'sellers' => $apporteur->referrals()->count(),
            'active_sellers' => $apporteur->referrals()->whereHas('articles', fn ($q) => $q->where('is_published', true))->count(),
            'first_sales' => $apporteur->commissions()->count(),
            'earned' => (int) $apporteur->commissions()->where('status', ApporteurCommission::PAID)->sum('amount'),
        ];
    }

    /**
     * Classement des apporteurs actifs sur une période : vendeurs apportés qui ont publié
     * au moins une annonce (un simple compte vide ne compte pas), puis gains de la période.
     *
     * @return Collection<int, array{apporteur: Apporteur, sellers: int, first_sales: int, earned: int}>
     */
    public function leaderboard(CarbonInterface $from, CarbonInterface $to, int $limit = 10): Collection
    {
        $apporteurs = Apporteur::with('user')
            ->where('status', Apporteur::ACTIVE)
            ->withCount(['referrals as sellers' => fn ($q) => $q
                ->whereBetween('referred_at', [$from, $to])
                ->whereHas('articles', fn ($a) => $a->where('is_published', true))])
            ->withCount(['commissions as first_sales' => fn ($q) => $q->whereBetween('created_at', [$from, $to])])
            ->withSum(['commissions as earned' => fn ($q) => $q
                ->whereBetween('created_at', [$from, $to])
                ->where('status', ApporteurCommission::PAID)], 'amount')
            ->get();

        return $apporteurs
            ->filter(fn ($a) => $a->sellers > 0 || $a->first_sales > 0)
            ->sortBy([['sellers', 'desc'], ['first_sales', 'desc'], ['earned', 'desc']])
            ->take($limit)
            ->map(fn ($a) => [
                'apporteur' => $a,
                'sellers' => (int) $a->sellers,
                'first_sales' => (int) $a->first_sales,
                'earned' => (int) $a->earned,
            ])
            ->values();
    }

    private function uniqueCode(): string
    {
        do {
            $code = '';
            for ($i = 0; $i < self::CODE_LENGTH; $i++) {
                $code .= self::CODE_ALPHABET[random_int(0, strlen(self::CODE_ALPHABET) - 1)];
            }
        } while (Apporteur::where('code', $code)->exists());

        return $code;
    }
}
