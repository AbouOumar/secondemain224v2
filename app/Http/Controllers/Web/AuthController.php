<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use App\Notifications\Auth\WelcomeNotification;
use App\Models\RoleRequest;
use App\Models\User;
use App\Services\Account\RoleRequestService;
use App\Services\Auth\LoginAttemptGuard;
use App\Services\Apporteur\ApporteurService;
use App\Rules\ActiveApporteurCode;
use App\Http\Controllers\Web\ApporteurController;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
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

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $login = (string) $request->login;
        $ip = (string) $request->ip();
        $guard = app(LoginAttemptGuard::class);

        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';
        $user = User::where($field, $login)->first();

        if ($wait = $guard->lockedFor($login, $ip, $user)) {
            return back()->withErrors(['login' => $guard->lockedMessage($wait)])->onlyInput('login');
        }

        if (! $user || ! Hash::check($request->password, $user->password)) {
            $remaining = $guard->failed($login, $ip, $user);
            $message = $remaining === 0
                ? $guard->lockedMessage($guard->lockedFor($login, $ip, $user))
                : $guard->failureMessage($remaining);

            return back()->withErrors(['login' => $message])->onlyInput('login');
        }

        $guard->succeeded($login, $ip, $user);
        if ($user->status?->value === 'suspendu') {
            return back()->withErrors(['login' => 'Ce compte a été suspendu.'])->onlyInput('login');
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return $this->redirectByRole();
    }

    public function showRegister(Request $request)
    {
        // Lien de parrainage (/parrainage/CODE ou /register?ref=CODE) : son code est pré-rempli,
        // y compris si le visiteur a parcouru le site avant de s'inscrire (code gardé en session).
        $code = $request->query('ref') ?? $request->session()->get(ApporteurController::SESSION_KEY);

        return view('auth.register', ['referralCode' => is_string($code) ? strtoupper($code) : null]);
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'nullable|email|unique:users,email',
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9 ]{6,20}$/', 'unique:users,phone'],
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|string|in:acheteur,vendeur,revendeur_pro,motard',
            'newsletter' => 'nullable|boolean',
            'referral_code' => ['nullable', 'string', 'max:20', new ActiveApporteurCode],
        ]);

        // Livreur et revendeur pro s'obtiennent sur demande, après examen (voir l'API d'inscription).
        $requested = $request->role ?? 'acheteur';
        $needsReview = in_array($requested, RoleRequest::REQUESTABLE, true);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
            'role' => $needsReview ? 'acheteur' : $requested,
            'status' => 'actif',
        ]);
        if ($needsReview) {
            app(RoleRequestService::class)->request($user, $requested, null);
        }

        if ($request->boolean('newsletter')) {
            $user->forceFill(['newsletter_subscribed_at' => now()])->save();
        }

        Wallet::create(['user_id' => $user->id]);
        if ($request->filled('referral_code')) {
            app(ApporteurService::class)->attachReferral($user, $request->referral_code);
        }
        $request->session()->forget(ApporteurController::SESSION_KEY);
        $user->notify(new WelcomeNotification);

        Auth::login($user);

        return $this->redirectByRole();
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
