<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('boosts:expire')->everyMinute();

Schedule::command('escrow:auto-release')->daily();

Schedule::command('offers:expire-stale')->hourly();

Schedule::command('search-alerts:check')->daily();

// Hébergement mutualisé : pas de worker de queue permanent possible.
// Le scheduler (déclenché par le cron LWS) traite la file toutes les minutes.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping();
