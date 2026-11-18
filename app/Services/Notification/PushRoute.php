<?php

namespace App\Services\Notification;

use App\Models\Article;

/**
 * Page de l'app mobile à ouvrir au toucher d'une notification
 * (chemins définis par src/lib/routes.ts de secondmain-mobile).
 */
class PushRoute
{
    public const FALLBACK = '/profile/';

    public static function forNotification(string $type, array $data): string
    {
        // Les types existent avec un point ou un tiret bas (« achat.valide », « nouvelle_offre »).
        $screen = match (str_replace('.', '_', $type)) {
            'nouvelle_offre', 'offre_contree', 'offre_acceptee', 'offre_refusee' => '/offers/',
            'achat_valide', 'nouvelle_commande', 'livraison_acceptee', 'livraison_livree', 'livraison_terminee' => '/orders/',
            'livraison_disponible', 'livraison_attribuee' => '/deliveries/',
            'retrait_traite', 'apporteur_gain' => '/wallet/',
            'apporteur_traite' => '/profile/apporteur/',
            'role_traite' => '/profile/role/',
            'paiement_confirme', 'paiement_recu', 'paiement_echoue' => '/orders/',
            'recharge_recue' => '/wallet/',
            'boost_active', 'annonce_validee', 'annonce_rejetee', 'annonce_vendue', 'annonce_retiree' => '/profile/listings/',
            'abonnement_active', 'abonnement_annule', 'abonnement_expire' => '/subscription/',
            'verification_approuvee', 'verification_rejetee' => '/verification/',
            'compte_suspendu', 'compte_avertissement', 'signalement_traite' => '/notifications/',
            default => null,
        };
        if ($screen) {
            return $screen;
        }

        $articleId = $data['article_id'] ?? ($data['article_ids'][0] ?? null);
        $slug = $articleId ? Article::whereKey($articleId)->value('slug') : null;

        return $slug ? '/article/?slug='.rawurlencode($slug) : self::FALLBACK;
    }

    public static function conversation(int $userId, ?int $articleId, string $name): string
    {
        return '/conversation/?'.http_build_query(array_filter([
            'user' => $userId,
            'article_id' => $articleId,
            'name' => $name,
        ]));
    }
}
