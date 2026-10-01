<?php

namespace App\Http\Controllers\Web;

use App\Enums\EmailCategory;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class EmailPreferenceController extends Controller
{
    public function edit(Request $request)
    {
        return view('profile.email-preferences', [
            'user' => $request->user(),
            'categories' => EmailCategory::cases(),
        ]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'categories' => 'array',
            'categories.*' => 'string',
        ]);

        $enabled = $request->input('categories', []);
        $user = $request->user();

        foreach (EmailCategory::cases() as $category) {
            $user->setEmailPreference($category, in_array($category->value, $enabled, true));
        }

        return back()->with('success', 'Vos préférences e-mail ont été enregistrées.');
    }

    /**
     * Page ouverte depuis le lien « Ne plus recevoir ces e-mails » (URL signée).
     * La désinscription se fait au clic sur le bouton, pas à l'ouverture du
     * lien, car certains logiciels de messagerie ouvrent les liens à l'avance.
     */
    public function showUnsubscribe(User $user, EmailCategory $category)
    {
        return view('emails.unsubscribe', [
            'user' => $user,
            'category' => $category,
            'done' => false,
        ]);
    }

    /**
     * Désinscription : bouton de la page ci-dessus, ou désinscription
     * en un clic des messageries (en-tête List-Unsubscribe-Post).
     */
    public function unsubscribe(User $user, EmailCategory $category)
    {
        $user->setEmailPreference($category, false);

        return view('emails.unsubscribe', [
            'user' => $user,
            'category' => $category,
            'done' => true,
        ]);
    }
}
