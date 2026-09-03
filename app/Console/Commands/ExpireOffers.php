<?php

namespace App\Console\Commands;

use App\Services\Offer\OfferService;
use Illuminate\Console\Command;

class ExpireOffers extends Command
{
    protected $signature = 'offers:expire-stale';
    protected $description = "Marque expirées les offres en attente dont le délai de réponse (48h) est dépassé";

    public function handle(OfferService $offers): void
    {
        $count = $offers->expireStale();

        $this->info("{$count} offre(s) expirée(s).");
    }
}
