<?php

namespace App\Console\Commands;

use App\Models\Article;
use App\Services\Article\BoostService;
use Illuminate\Console\Command;

class ExpireBoosts extends Command
{
    protected $signature = 'boosts:expire';
    protected $description = 'Expire les boosts dont le temps est écoulé';

    public function handle(BoostService $boostService): void
    {
        $expired = Article::where('is_boosted', true)
            ->where('boosted_until', '<=', now())
            ->get();

        $count = $expired->count();
        foreach ($expired as $article) {
            $boostService->expireBoost($article);
        }

        $this->info("{$count} boost(s) expiré(s).");
    }
}
