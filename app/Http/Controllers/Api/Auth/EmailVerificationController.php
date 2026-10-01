<?php
namespace App\Http\Controllers\Api\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmailVerificationController extends Controller
{
    /**
     * Renvoie le lien de confirmation d'adresse e-mail (le lien ouvre le site web).
     */
    public function store(Request $request) {
        $user = $request->user();
        if (!$user->email) {
            return response()->json(['message' => "Aucune adresse e-mail n'est associée à ce compte."], 422);
        }
        if ($user->hasVerifiedEmail()) {
            return response()->json(['message' => 'Adresse e-mail déjà confirmée.']);
        }
        $user->sendEmailVerificationNotification();
        return response()->json(['message' => 'Lien de confirmation envoyé.']);
    }
}
