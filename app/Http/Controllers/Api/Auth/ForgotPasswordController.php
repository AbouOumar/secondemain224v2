<?php
namespace App\Http\Controllers\Api\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\PasswordResetToken;

class ForgotPasswordController extends Controller
{
    public function store(Request $request) {
        if ($request->filled('email')) {
            $request->validate(['email' => 'required|email']);
            // Réponse identique que le compte existe ou non (pas d'énumération des e-mails).
            Password::sendResetLink($request->only('email'));
            return response()->json(['message' => 'Si un compte correspond à cette adresse, un lien de réinitialisation vient d\'être envoyé.']);
        }

        $request->validate(['phone' => 'required|string|exists:users,phone']);
        $token = Str::random(60);
        PasswordResetToken::updateOrCreate(
            ['phone' => $request->phone],
            ['token' => Hash::make($token), 'created_at' => now()]
        );
        return response()->json(['message' => 'Code de réinitialisation envoyé.']);
    }

    public function reset(Request $request) {
        $request->validate([
            'phone' => 'required|string',
            'token' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);
        $reset = PasswordResetToken::where('phone', $request->phone)->first();
        if (!$reset || !Hash::check($request->token, $reset->token)) {
            return response()->json(['message' => 'Token invalide.'], 400);
        }
        User::where('phone', $request->phone)->update(['password' => Hash::make($request->password)]);
        $reset->delete();
        return response()->json(['message' => 'Mot de passe réinitialisé.']);
    }
}
