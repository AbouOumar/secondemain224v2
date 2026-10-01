<?php

namespace App\Console\Commands;

use App\Services\Newsletter\NewsletterSender;
use Illuminate\Console\Command;

class SendNewsletterBatch extends Command
{
    protected $signature = 'newsletter:send-batch {--limit= : Nombre maximum d\'e-mails pour ce lot}';

    protected $description = 'Envoie le prochain lot des campagnes de newsletter en cours';

    public function handle(NewsletterSender $sender): int
    {
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;
        $count = $sender->sendNextBatch($limit);

        if ($count > 0) {
            $this->info("{$count} e-mail(s) de newsletter traité(s).");
        }

        return self::SUCCESS;
    }
}
