<?php
namespace App\Services\Payment;
interface PaymentGatewayInterface {
    public function initiatePayment(float $montant, string $currency, string $reference, array $metadata): array;
    public function verifyPayment(string $externalRef): array;
    public function processWebhook(array $payload): array;
}
