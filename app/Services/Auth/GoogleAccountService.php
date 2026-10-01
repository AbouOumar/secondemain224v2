<?php

namespace App\Services\Auth;

use App\Models\OauthProvider;
use App\Models\User;
use App\Models\Wallet;
use App\Notifications\Auth\WelcomeNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Logique commune à la connexion Google (site Blade et API mobile/Next.js) :
 * vérification d'un ID token auprès de Google et rattachement du profil
 * Google à un compte local.
 */
class GoogleAccountService
{
    private const ISSUERS = ['accounts.google.com', 'https://accounts.google.com'];

    /**
     * Vérifie un ID token Google (signature, expiration, audience, émetteur).
     * Retourne le profil Google, ou null si le token est invalide.
     */
    public function verifyIdToken(string $idToken): ?array
    {
        $clientId = config('services.google.client_id');

        if (! $clientId) {
            return null;
        }

        $response = Http::get('https://oauth2.googleapis.com/tokeninfo', ['id_token' => $idToken]);

        if (! $response->ok()) {
            return null;
        }

        $claims = $response->json();

        if (($claims['aud'] ?? null) !== $clientId
            || ! in_array($claims['iss'] ?? null, self::ISSUERS, true)
            || (int) ($claims['exp'] ?? 0) < time()
            || empty($claims['sub'])) {
            return null;
        }

        return $claims;
    }

    /**
     * Retrouve ou crée l'utilisateur correspondant au profil Google.
     * Retourne null si l'adresse e-mail n'a pas été vérifiée par Google.
     */
    public function resolveUser(array $profile): ?User
    {
        $email = $profile['email'] ?? null;

        // Google renvoie email_verified en booléen (userinfo) ou en chaîne (tokeninfo).
        if (! $email || ! filter_var($profile['email_verified'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        $provider = OauthProvider::where('provider', 'google')
            ->where('provider_id', $profile['sub'])
            ->first();

        if ($provider) {
            return $provider->user;
        }

        $user = User::where('email', $email)->first();
        $created = false;

        if (! $user) {
            $name = $profile['name'] ?? trim(($profile['given_name'] ?? '').' '.($profile['family_name'] ?? ''));

            $user = User::create([
                'name' => $name ?: 'Utilisateur Google',
                'email' => $email,
                'phone' => 'g_'.Str::random(12),
                'password' => Hash::make(Str::random(32)),
                'role' => 'acheteur',
                'status' => 'actif',
                'avatar' => $profile['picture'] ?? null,
            ]);

            Wallet::create(['user_id' => $user->id]);
            $created = true;
        }

        OauthProvider::create([
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_id' => $profile['sub'],
        ]);

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        if ($created) {
            $user->notify(new WelcomeNotification);
        }

        return $user;
    }
}
