<?php

/*
|--------------------------------------------------------------------------
| Informations légales
|--------------------------------------------------------------------------
|
| Identité de l'exploitant affichée dans la politique de confidentialité
| et les CGU. Les valeurs par défaut sont celles des documents juridiques (dossier docs/) ;
| elles peuvent être remplacées
| (ici ou dans le .env) avant la mise en ligne. Forme juridique et RCCM
| sont masqués tant qu'ils ne sont pas renseignés.
|
*/

return [
    'company_name' => env('LEGAL_COMPANY_NAME') ?: 'Seconde main 224',
    'legal_form' => env('LEGAL_FORM') ?: null, // ex. SARLU : la ligne est masquée tant qu'elle est vide
    'rccm' => env('LEGAL_RCCM') ?: null, // idem
    'address' => env('LEGAL_ADDRESS') ?: 'Siège à Gbessia (carrefour Saya), Conakry, Guinée',
    'email' => env('LEGAL_EMAIL') ?: 'secondemain880@gmail.com',
    'phone' => env('LEGAL_PHONE') ?: '+224 666 01 25 49',
    'website' => 'www.secondemain224.com',
    'slogan' => 'Acheter malin. Vendre utile.',
    // Valeurs brutes (null si non renseignées) pour les pages publiques,
    // qui masquent la ligne plutôt que d'afficher « [à compléter] ».
    'public_phone' => env('LEGAL_PHONE') ?: null,
    'public_email' => env('LEGAL_EMAIL') ?: null,

    // Réseaux sociaux du pied de page : masqués tant qu'ils ne sont pas renseignés.
    'social' => [
        'facebook' => env('SOCIAL_FACEBOOK_URL') ?: null,
        'whatsapp' => env('SOCIAL_WHATSAPP_NUMBER') ?: null, // ex. 224620000000
        'twitter' => env('SOCIAL_TWITTER_URL') ?: null,
    ],

    'host' => 'LWS (Ligne Web Services), France — www.lws.fr',
    'updated_at' => '21 octobre 2026',
];
