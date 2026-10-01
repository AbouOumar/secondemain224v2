<?php

namespace App\Enums;

/**
 * Catégories d'e-mails que l'utilisateur peut activer ou désactiver une à une.
 * Toutes sont actives par défaut, sauf la newsletter (inscription volontaire).
 */
enum EmailCategory: string
{
    case Messages = 'messages';
    case Offres = 'offres';
    case Commandes = 'commandes';
    case Livraisons = 'livraisons';
    case Alertes = 'alertes';
    case Newsletter = 'newsletter';

    public function label(): string
    {
        return match ($this) {
            self::Messages => 'Nouveaux messages',
            self::Offres => 'Offres et négociations',
            self::Commandes => 'Commandes',
            self::Livraisons => 'Livraisons',
            self::Alertes => 'Alertes de recherche',
            self::Newsletter => 'Newsletter',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Messages => "Quand quelqu'un vous écrit (un e-mail par heure et par conversation au maximum).",
            self::Offres => 'Offre reçue, contre-offre, offre acceptée ou refusée.',
            self::Commandes => 'Confirmation de vos achats et nouvelles commandes sur vos articles.',
            self::Livraisons => 'Suivi des livraisons et missions des livreurs.',
            self::Alertes => 'Nouvelles annonces correspondant à vos alertes.',
            self::Newsletter => 'Nouveautés et bons plans de Seconde Main 224, quelques e-mails par mois au maximum.',
        };
    }

    /**
     * Catégorie d'un type de notification ; null = pas d'e-mail pour ce type.
     */
    public static function forNotificationType(string $type): ?self
    {
        return match (true) {
            in_array($type, ['nouvelle_offre', 'offre_contree', 'offre_acceptee', 'offre_refusee'], true) => self::Offres,
            in_array($type, ['achat.valide', 'achat_valide', 'nouvelle.commande'], true) => self::Commandes,
            str_starts_with($type, 'livraison') => self::Livraisons,
            $type === 'alerte_recherche' => self::Alertes,
            $type === 'nouveau_message' => self::Messages,
            default => null,
        };
    }
}
