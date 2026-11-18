<?php

namespace App\Console\Commands;

use App\Services\Djomy\DjomyService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

/**
 * Paiement de test Djomy, sans commande ni écriture en base.
 *   php artisan djomy:test-payment --phone=224622000000 --amount=1000
 *   php artisan djomy:test-payment --status=<transactionId>
 * Refuse de tourner hors sandbox.
 */
class DjomyTestPayment extends Command
{
    protected $signature = 'djomy:test-payment
        {--phone= : Numéro du payeur, format international sans + (ex. 224622000000)}
        {--amount=1000 : Montant en GNF}
        {--status= : Consulter le statut d\'une transaction au lieu d\'en créer une}';

    protected $description = 'Lance un paiement de test dans le sandbox Djomy, ou consulte son statut';

    public function handle(DjomyService $djomy): int
    {
        if (! config('djomy.sandbox')) {
            $this->error('DJOMY_SANDBOX est à false : cette commande ne tourne qu\'en sandbox.');

            return self::FAILURE;
        }

        if ($id = $this->option('status')) {
            return $this->report($djomy->getPaymentStatus($id), 'Statut de la transaction');
        }

        $phone = preg_replace('/\D/', '', (string) $this->option('phone'));
        if ($phone === '') {
            $this->error('Indiquez --phone=224XXXXXXXXX');

            return self::FAILURE;
        }

        $reference = 'TEST-'.strtoupper(Str::random(10));
        $result = $djomy->initiateGatewayPayment([
            'amount' => (int) $this->option('amount'),
            'payer_phone' => $phone,
            'description' => 'Paiement de test Seconde Main 224',
            'reference' => $reference,
            'return_url' => url('/'),
            'cancel_url' => url('/'),
        ]);

        $this->line("Référence marchand : {$reference}");

        return $this->report($result, 'Paiement initié');
    }

    private function report(array $result, string $title): int
    {
        if (! ($result['success'] ?? false)) {
            $this->error('Échec : '.json_encode($result['error'] ?? $result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

            return self::FAILURE;
        }

        $this->info($title);
        $data = $result['data'] ?? [];
        foreach (['transactionId', 'status', 'paidAmount', 'amount', 'paymentMethod', 'redirectUrl', 'paymentUrl'] as $key) {
            if (isset($data[$key])) {
                $this->line(str_pad($key, 14).': '.(is_scalar($data[$key]) ? $data[$key] : json_encode($data[$key])));
            }
        }
        $this->line('Réponse complète : '.json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }
}
