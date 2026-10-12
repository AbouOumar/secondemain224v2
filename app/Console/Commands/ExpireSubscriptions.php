<?php

namespace App\Console\Commands;

use App\Services\Subscription\SubscriptionService;
use Illuminate\Console\Command;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';
    protected $description = 'Expire les abonnements arrivés à terme et retire le badge revendeur vérifié';

    public function handle(SubscriptionService $subscriptions): int
    {
        $count = $subscriptions->expireDue();
        $this->info("{$count} abonnement(s) expiré(s).");

        return self::SUCCESS;
    }
}
