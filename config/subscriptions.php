<?php

/*
|--------------------------------------------------------------------------
| Abonnements des revendeurs professionnels
|--------------------------------------------------------------------------
|
| Prix en GNF par mois, payés avec le portefeuille. Les libellés d'avantages sont
| ceux affichés sur la page d'abonnement du site. `badge` : le plan donne le badge
| « revendeur vérifié » tant qu'il est actif. Les plans payants durent `duration_days`
| jours et ne se renouvellent pas tout seuls.
|
*/
return [
    'duration_days' => (int) env('SUBSCRIPTION_DURATION_DAYS', 30),

    'plans' => [
        'basic' => [
            'label' => 'Basique',
            'tagline' => 'Parfait pour commencer',
            'price' => 0,
            'badge' => false,
            'features' => ['Annonces illimitées', 'Gestion de base des commandes', 'Support standard'],
        ],
        'pro' => [
            'label' => 'Pro',
            'tagline' => 'Pour les vendeurs sérieux',
            'price' => (int) env('SUBSCRIPTION_PRICE_PRO', 5000),
            'badge' => true,
            'features' => [
                'Toutes les fonctionnalités de base',
                'Badge revendeur vérifié',
                'Outils de promotion avancés',
                'Statistiques détaillées',
                'Gestion de stock',
                'Support prioritaire',
            ],
        ],
        'enterprise' => [
            'label' => 'Entreprise',
            'tagline' => 'Pour les grandes opérations',
            'price' => (int) env('SUBSCRIPTION_PRICE_ENTERPRISE', 15000),
            'badge' => true,
            'features' => [
                'Toutes les fonctionnalités Pro',
                'Compte dédié gestionnaire',
                'API personnelle',
                'Support 24/7',
                'Formation personnalisée',
            ],
        ],
    ],
];
