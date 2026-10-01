<?php
namespace App\Http\Controllers\Api\Auth;
use App\Http\Controllers\Controller;
use App\Services\Auth\GoogleAccountService;
use Illuminate\Http\Request;
use App\Http\Resources\UserResource;

class SocialAuthController extends Controller
{
    /**
     * Connexion avec Google : le client envoie l'ID token obtenu via
     * Google Identity Services, vérifié ici auprès de Google.
     */
    public function google(Request $request, GoogleAccountService $accounts) {
        $request->validate(['token' => 'required|string']);

        $profile = $accounts->verifyIdToken($request->token);
        if (!$profile) {
            return response()->json(['message' => 'Jeton Google invalide.'], 401);
        }

        $user = $accounts->resolveUser($profile);
        if (!$user) {
            return response()->json(['message' => "Votre adresse e-mail Google n'est pas vérifiée."], 422);
        }

        if ($user->status?->value === 'suspendu') {
            return response()->json(['message' => 'Ce compte a été suspendu.'], 403);
        }

        $token = $user->createToken('auth-token')->plainTextToken;
        $user->update(['last_online_at' => now()]);
        return response()->json(['user' => new UserResource($user), 'token' => $token]);
    }

    /**
     * Désactivé tant que la vérification du jeton Facebook n'est pas implémentée.
     */
    public function facebook() {
        return response()->json(['message' => "La connexion avec Facebook n'est pas disponible."], 501);
    }
}
