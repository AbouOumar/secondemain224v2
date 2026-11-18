<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Commission plateforme
    |--------------------------------------------------------------------------
    |
    | Pourcentage (0 à 1) prélevé sur le prix de l'article lors de la
    | libération de l'escrow au vendeur. 0 = pas de commission.
    | Particuliers : commission_rate. Magasins partenaires vérifiés : le taux
    | réglé par l'admin sur le magasin (entre les bornes), sinon partner_commission_rate.
    |
    */
    'commission_rate' => (float) env('MARKETPLACE_COMMISSION_RATE', 0.05),
    'partner_commission_rate' => (float) env('MARKETPLACE_PARTNER_COMMISSION_RATE', 0.10),
    'partner_commission_min' => 0.07,
    'partner_commission_max' => 0.15,

    /*
    |--------------------------------------------------------------------------
    | Apporteurs d'affaires
    |--------------------------------------------------------------------------
    |
    | Part (0 à 1) de la commission de la plateforme reversée à l'apporteur
    | sur la première vente de chaque vendeur qu'il a recruté.
    |
    */
    'apporteur_share' => (float) env('APPORTEUR_SHARE', 0.30),

    /*
    |--------------------------------------------------------------------------
    | Libération automatique de l'escrow (filet de sécurité)
    |--------------------------------------------------------------------------
    |
    | Si l'acheteur ne confirme jamais la réception, les fonds sont libérés
    | automatiquement au vendeur après ce délai, pour éviter qu'ils restent
    | bloqués indéfiniment. Ne s'applique pas aux commandes en litige.
    |
    */
    'escrow_auto_release_delivered_hours' => (int) env('ESCROW_AUTO_RELEASE_DELIVERED_HOURS', 72),
    'escrow_auto_release_no_delivery_days' => (int) env('ESCROW_AUTO_RELEASE_NO_DELIVERY_DAYS', 7),

];
