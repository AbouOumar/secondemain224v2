<?php
namespace App\Http\Controllers\Api\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function store(Request $request) {
        $request->validate(['email' => 'required|email']);
        // Réponse identique que le compte existe ou non (pas d'énumération des e-mails).
        Password::sendResetLink($request->only('email'));
        return response()->json(['message' => 'Si un compte correspond à cette adresse, un lien de réinitialisation vient d\'être envoyé.']);
    }

    public function reset(Request $request) {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->forceFill(['password' => Hash::make($password)])->save();
            }
        );
        if ($status !== Password::PASSWORD_RESET) {
            return response()->json(['message' => 'Lien de réinitialisation invalide ou expiré.'], 400);
        }
        return response()->json(['message' => 'Mot de passe réinitialisé.']);
    }
}
