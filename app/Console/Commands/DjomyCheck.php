<?php

namespace App\Console\Commands;

use App\Services\Djomy\DjomyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Vérifie la configuration Djomy : identifiants présents, environnement visé
 * et obtention d'un jeton d'accès. N'affiche jamais les identifiants ni le jeton.
 */
class DjomyCheck extends Command
{
    protected $signature = 'djomy:check';

    protected $description = 'Vérifie que les identifiants Djomy sont acceptés (sans les afficher)';

    public function handle(DjomyService $djomy): int
    {
        $id = (string) config('djomy.client_id');
        $secret = (string) config('djomy.client_secret');
        $sandbox = (bool) config('djomy.sandbox');

        $this->line('Client ID     : '.($id !== '' ? 'renseigné' : '<error>VIDE</error>'));
        $this->line('Client secret : '.($secret !== '' ? 'renseigné' : '<error>VIDE</error>'));
        $this->line('Environnement : '.($sandbox ? 'sandbox' : '<comment>PRODUCTION</comment>').' ('.($sandbox ? config('djomy.sandbox_url') : config('djomy.production_url')).')');
        $this->line('Webhook       : '.rtrim((string) config('app.url'), '/').config('djomy.webhook_url'));

        if ($id === '' || $secret === '') {
            $this->error('Renseignez DJOMY_CLIENT_ID et DJOMY_CLIENT_SECRET dans .env, puis lancez php artisan config:clear.');

            return self::FAILURE;
        }

        Cache::forget('djomy_access_token');
        $token = $djomy->getAccessToken();

        if (! $token) {
            $this->error('Djomy a refusé l\'authentification. Détail dans storage/logs/laravel.log (« Djomy auth failed »).');

            return self::FAILURE;
        }

        $this->info('Authentification réussie : Djomy a délivré un jeton d\'accès.');

        return self::SUCCESS;
    }
}
