<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * Pages d'information du site (CGU, confidentialité…) servies à l'app mobile
 * à partir des mêmes vues partielles que le site : une seule source de vérité.
 */
class PageController extends Controller
{
    private const PAGES = [
        'terms' => "Conditions générales d'utilisation",
        'privacy' => 'Politique de confidentialité',
        'account-deletion' => 'Supprimer mon compte',
        'about' => 'Qui sommes-nous ?',
    ];

    public function show(string $slug)
    {
        abort_unless(array_key_exists($slug, self::PAGES), 404);

        return response()->json(['data' => [
            'slug' => $slug,
            'title' => self::PAGES[$slug],
            'updated_at' => config('legal.updated_at'),
            'html' => view('pages.'.$slug)->render(),
        ]]);
    }
}
