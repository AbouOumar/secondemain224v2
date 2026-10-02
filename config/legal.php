<?php

/*
|--------------------------------------------------------------------------
| Informations légales
|--------------------------------------------------------------------------
|
| Identité de l'exploitant affichée dans la politique de confidentialité
| et les CGU. Les valeurs « [à compléter] » doivent être renseignées
| (ici ou dans le .env) avant la mise en ligne.
|
*/

return [
    'company_name' => env('LEGAL_COMPANY_NAME') ?: '[à compléter : raison sociale]',
    'legal_form' => env('LEGAL_FORM') ?: '[à compléter : forme juridique, ex. SARLU]',
    'rccm' => env('LEGAL_RCCM') ?: '[à compléter : numéro RCCM]',
    'address' => env('LEGAL_ADDRESS') ?: '[à compléter : adresse], Conakry, Guinée',
    'email' => env('LEGAL_EMAIL') ?: '[à compléter : e-mail de contact]',
    'phone' => env('LEGAL_PHONE') ?: '[à compléter : téléphone]',
    'host' => 'LWS (Ligne Web Services), France — www.lws.fr',
    'updated_at' => '2 octobre 2026',
];
