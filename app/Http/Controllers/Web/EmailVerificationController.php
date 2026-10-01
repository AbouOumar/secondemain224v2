<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmailVerificationController extends Controller
{
    /**
     * Lien reçu par e-mail (URL signée). Ne demande pas d'être connecté :
     * l'utilisateur peut ouvrir le lien sur un autre appareil.
     */
    public function verify(Request $request, int $id, string $hash)
    {
        $user = User::find($id);

        if (! $user || ! $user->email || ! hash_equals(sha1($user->email), $hash)) {
            return redirect()->route('login')->withErrors(['login' => 'Lien de confirmation invalide.']);
        }

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
            event(new Verified($user));
        }

        $message = 'Votre adresse e-mail est confirmée. Merci !';

        return Auth::check()
            ? redirect()->route('profile.dashboard')->with('success', $message)
            : redirect()->route('login')->with('status', $message);
    }

    /**
     * Renvoie le lien de confirmation à l'utilisateur connecté.
     */
    public function send(Request $request)
    {
        $user = $request->user();

        if (! $user->email) {
            return back()->with('error', "Ajoutez d'abord une adresse e-mail à votre profil.");
        }

        if ($user->hasVerifiedEmail()) {
            return back()->with('success', 'Votre adresse e-mail est déjà confirmée.');
        }

        $user->sendEmailVerificationNotification();

        return back()->with('success', 'Un nouveau lien de confirmation vous a été envoyé.');
    }
}
