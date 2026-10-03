<?php
namespace App\Http\Controllers\Web;
use App\Http\Controllers\Controller;
use App\Notifications\Auth\WelcomeNotification;
use App\Models\RoleRequest;
use App\Models\User;
use App\Services\Account\RoleRequestService;
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

        $credentials = $request->only('password');
        $login = $request->login;

        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'phone';

        if (Auth::attempt([$field => $login, 'password' => $request->password], $request->boolean('remember'))) {
            $request->session()->regenerate();
            return $this->redirectByRole();
        }

        return back()->withErrors(['login' => 'Identifiants incorrects.'])->onlyInput('login');
    }

    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:191',
            'email' => 'nullable|email|unique:users,email',
            'phone' => 'required|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|string|in:acheteur,vendeur,revendeur_pro,motard',
            'newsletter' => 'nullable|boolean',
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
