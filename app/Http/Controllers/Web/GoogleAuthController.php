<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    protected function redirectByRole()
    {
        return match (Auth::user()->role?->value) {
            'admin' => redirect()->intended(route('admin.dashboard')),
            'revendeur_pro' => redirect()->intended('/seller/pro/tableau-de-bord'),
            'motard' => redirect()->intended('/motard/tableau-de-bord'),
            'vendeur' => redirect()->intended('/profile/listings'),
            default => redirect()->intended('/'),
        };
    }

    protected function redirectUri(): string
    {
        return config('services.google.redirect') ?: route('auth.google.callback');
    }

    /**
     * Redirect the user to Google's consent screen.
     */
    public function redirect(Request $request)
    {
        if (! config('services.google.client_id') || ! config('services.google.client_secret')) {
            return back()->withErrors(['login' => "La connexion avec Google n'est pas configurée pour le moment."]);
        }

        $state = Str::random(40);
        $request->session()->put('google_oauth_state', $state);

        $query = http_build_query([
            'client_id' => config('services.google.client_id'),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ]);

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?'.$query);
    }

    /**
     * Handle the callback from Google, log the user in (or create an account).
     */
    public function callback(Request $request, GoogleAccountService $accounts)
    {
        $expectedState = $request->session()->pull('google_oauth_state');

        if ($request->filled('error')) {
            return redirect()->route('login')->withErrors(['login' => 'Connexion avec Google annulée.']);
        }

        if (! $request->filled('state') || ! $expectedState || ! hash_equals($expectedState, $request->string('state')->value())) {
            return redirect()->route('login')->withErrors(['login' => 'Session Google invalide, veuillez réessayer.']);
        }

        if (! $request->filled('code')) {
            return redirect()->route('login')->withErrors(['login' => 'Connexion avec Google échouée.']);
        }

        $tokenResponse = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'code' => $request->string('code')->value(),
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if (! $tokenResponse->ok() || ! $tokenResponse->json('access_token')) {
            return redirect()->route('login')->withErrors(['login' => 'Connexion avec Google échouée.']);
        }

        $googleUser = Http::withToken($tokenResponse->json('access_token'))
            ->get('https://www.googleapis.com/oauth2/v3/userinfo');

        if (! $googleUser->ok() || ! $googleUser->json('sub')) {
            return redirect()->route('login')->withErrors(['login' => 'Impossible de récupérer votre profil Google.']);
        }

        $user = $accounts->resolveUser($googleUser->json());

        if (! $user) {
            return redirect()->route('login')->withErrors(['login' => "Votre adresse e-mail Google n'est pas vérifiée."]);
        }

        if ($user->status?->value === 'suspendu') {
            return redirect()->route('login')->withErrors(['login' => 'Ce compte a été suspendu.']);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        if (! $user->hasRealPhone()) {
            return redirect()->route('profile.edit')
                ->with('success', 'Bienvenue ! Ajoutez votre numéro de téléphone pour finaliser votre profil.');
        }

        return $this->redirectByRole();
    }
}
