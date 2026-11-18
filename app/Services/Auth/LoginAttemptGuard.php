<?php

namespace App\Services\Auth;

use App\Models\User;
use App\Notifications\Auth\AccountLockedNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Contrôle des tentatives de connexion (site et API).
 *
 * Deux verrous, en plus de la limite de débit par IP des routes :
 *  - compte + IP : 5 échecs en 15 min bloquent ce couple 15 min ;
 *  - compte seul : 20 échecs en 1 h, toutes IP confondues, bloquent le compte 1 h
 *    (attaque répartie sur plusieurs adresses).
 *
 * Le compte est identifié par l'utilisateur trouvé (e-mail ou téléphone mènent au
 * même compteur), sinon par l'identifiant saisi, normalisé.
 * Une réinitialisation du mot de passe lève le verrou du compte.
 */
class LoginAttemptGuard
{
    public const MAX_PER_IP = 5;
    public const IP_WINDOW = 900;
    public const IP_LOCK = 900;

    public const MAX_PER_ACCOUNT = 20;
    public const ACCOUNT_WINDOW = 3600;
    public const ACCOUNT_LOCK = 3600;

    /** Avertir l'utilisateur à partir de ce nombre d'échecs. */
    private const WARN_FROM = 3;

    public function findUser(string $login): ?User
    {
        $login = trim($login);
        if ($login === '') {
            return null;
        }

        return User::where('email', $login)->orWhere('phone', $login)->first();
    }

    /** Secondes avant de pouvoir réessayer, 0 si la tentative est permise. */
    public function lockedFor(string $login, string $ip, ?User $user): int
    {
        $subject = $this->subject($login, $user);

        return max(
            $this->remaining($this->lockKey($subject, $ip)),
            $this->remaining($this->lockKey($subject)),
        );
    }

    /**
     * Enregistre un échec. Renvoie le nombre de tentatives restantes avant blocage
     * du couple compte + IP (0 si ce dernier échec vient de bloquer).
     */
    public function failed(string $login, string $ip, ?User $user): int
    {
        $subject = $this->subject($login, $user);

        $ipFailures = $this->count($this->failKey($subject, $ip), self::IP_WINDOW);
        $accountFailures = $this->count($this->failKey($subject), self::ACCOUNT_WINDOW);

        if ($accountFailures >= self::MAX_PER_ACCOUNT) {
            $this->lock($subject, null, self::ACCOUNT_LOCK, $user, $ip);
        }
        if ($ipFailures >= self::MAX_PER_IP) {
            $this->lock($subject, $ip, self::IP_LOCK, $user, $ip);

            return 0;
        }

        return self::MAX_PER_IP - $ipFailures;
    }

    public function succeeded(string $login, string $ip, ?User $user): void
    {
        $subject = $this->subject($login, $user);
        Cache::forget($this->failKey($subject, $ip));
        Cache::forget($this->failKey($subject));
    }

    /** Après un nouveau mot de passe : le titulaire a repris la main sur son compte. */
    public function clearFor(User $user, ?string $ip = null): void
    {
        $subject = $this->subject('', $user);
        Cache::forget($this->failKey($subject));
        Cache::forget($this->lockKey($subject));
        if ($ip !== null) {
            Cache::forget($this->failKey($subject, $ip));
            Cache::forget($this->lockKey($subject, $ip));
        }
    }

    /** Message d'erreur après un échec, avec les tentatives restantes quand il en reste peu. */
    public function failureMessage(int $remaining): string
    {
        if ($remaining === 0) {
            return $this->lockedMessage(self::IP_LOCK);
        }
        if (self::MAX_PER_IP - $remaining >= self::WARN_FROM) {
            return 'Identifiants incorrects. '.($remaining === 1
                ? 'Dernière tentative avant blocage temporaire.'
                : "Il vous reste {$remaining} tentatives avant blocage temporaire.");
        }

        return 'Identifiants incorrects.';
    }

    public function lockedMessage(int $seconds): string
    {
        $minutes = max(1, (int) ceil($seconds / 60));

        return "Trop de tentatives de connexion. Réessayez dans {$minutes} minute".($minutes > 1 ? 's' : '').'.';
    }

    private function lock(string $subject, ?string $ip, int $seconds, ?User $user, string $requestIp): void
    {
        $key = $this->lockKey($subject, $ip);
        if ($this->remaining($key) > 0) {
            return;
        }

        Cache::put($key, now()->addSeconds($seconds)->getTimestamp(), $seconds);
        Cache::forget($this->failKey($subject, $ip));

        Log::warning('Connexion bloquée après trop d\'échecs', [
            'portee' => $ip === null ? 'compte' : 'compte+ip',
            'user_id' => $user?->id,
            'ip' => $requestIp,
            'duree_s' => $seconds,
        ]);

        if ($user) {
            try {
                $user->notify(new AccountLockedNotification($seconds, $requestIp));
            } catch (\Throwable $e) {
                report($e); // un e-mail qui échoue ne doit pas casser la connexion
            }
        }
    }

    private function count(string $key, int $window): int
    {
        Cache::add($key, 0, $window);

        return (int) Cache::increment($key);
    }

    private function remaining(string $lockKey): int
    {
        $until = Cache::get($lockKey);

        return $until ? max(0, (int) $until - now()->getTimestamp()) : 0;
    }

    private function subject(string $login, ?User $user): string
    {
        if ($user) {
            return 'u'.$user->id;
        }

        $normalized = mb_strtolower(preg_replace('/\s+/', '', $login));

        return 'l'.hash('sha256', $normalized);
    }

    private function failKey(string $subject, ?string $ip = null): string
    {
        return 'login-fail:'.$subject.($ip !== null ? '|'.$ip : '');
    }

    private function lockKey(string $subject, ?string $ip = null): string
    {
        return 'login-lock:'.$subject.($ip !== null ? '|'.$ip : '');
    }
}
