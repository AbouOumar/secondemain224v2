<?php

namespace App\Console\Commands;

use App\Models\SearchAlert;
use App\Services\Search\SearchAlertService;
use Illuminate\Console\Command;

class CheckSearchAlerts extends Command
{
    protected $signature = 'search-alerts:check';
    protected $description = "Vérifie les alertes de recherche actives et notifie les nouvelles annonces correspondantes";

    public function handle(SearchAlertService $service): void
    {
        $alerts = SearchAlert::where('is_active', true)->get();
        $notified = 0;

        foreach ($alerts as $alert) {
            if ($service->checkAndNotify($alert) > 0) {
                $notified++;
            }
        }

        $this->info("{$notified} alerte(s) avec de nouvelles annonces, sur {$alerts->count()} vérifiée(s).");
    }
}
