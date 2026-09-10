<?php

namespace App\Services\Search;

use App\Models\Article;
use App\Models\SearchAlert;
use App\Services\Notification\FirebaseNotificationService;

/**
 * Vérifie les alertes de recherche enregistrées et notifie leur
 * propriétaire des nouvelles annonces correspondantes. Réutilise
 * Article::scopeMatchingCriteria() — le même filtrage que la recherche
 * du site (HomeController::search) — pour garantir qu'une alerte trouve
 * exactement ce que la recherche équivalente aurait trouvé.
 */
class SearchAlertService
{
    public function __construct(private FirebaseNotificationService $notif) {}

    /**
     * Initialise le curseur d'une alerte tout juste créée sur le dernier
     * article existant : une alerte ne notifie que les annonces qui
     * apparaissent *après* sa création, pas celles déjà visibles au moment
     * où l'utilisateur a défini ses critères (il vient de les voir en
     * cherchant).
     */
    public function initializeCursor(SearchAlert $alert): void
    {
        $alert->update(['last_matched_article_id' => Article::max('id') ?? 0]);
    }

    public function matchingArticlesQuery(SearchAlert $alert)
    {
        return Article::disponible()
            ->where('is_published', true)
            ->matchingCriteria([
                'search' => $alert->search,
                'category' => $alert->category_id,
                'min_price' => $alert->min_price,
                'max_price' => $alert->max_price,
                'etat' => $alert->etat,
                'localisation' => $alert->localisation,
            ]);
    }

    /**
     * Cherche les annonces correspondantes plus récentes que la dernière
     * vérification, notifie l'utilisateur si des nouveautés existent, et
     * avance le curseur. Retourne le nombre d'annonces notifiées.
     */
    public function checkAndNotify(SearchAlert $alert): int
    {
        $query = $this->matchingArticlesQuery($alert);

        if ($alert->last_matched_article_id) {
            $query->where('id', '>', $alert->last_matched_article_id);
        }

        $matches = $query->orderBy('id')->limit(20)->get();

        if ($matches->isEmpty()) {
            $alert->update(['last_checked_at' => now()]);
            return 0;
        }

        $count = $matches->count();
        $title = $count === 1
            ? 'Nouvelle annonce correspondant à votre alerte'
            : "{$count} nouvelles annonces correspondant à votre alerte";
        $preview = $matches->pluck('titre')->take(3)->implode(', ') . ($count > 3 ? '…' : '');

        $this->notif->send(
            $alert->user,
            $title,
            $preview . ' — ' . $alert->label(),
            'alerte_recherche',
            ['alert_id' => $alert->id, 'article_ids' => $matches->pluck('id')->all()]
        );

        $alert->update([
            'last_matched_article_id' => $matches->max('id'),
            'last_checked_at' => now(),
        ]);

        return $count;
    }
}
