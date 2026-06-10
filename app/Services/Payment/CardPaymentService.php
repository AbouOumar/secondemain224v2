<?php
namespace App\Services\Payment;
class CardPaymentService implements PaymentGatewayInterface {
    public function initiatePayment(float $montant, string $currency, string $reference, array $metadata): array {
        // TODO: Intégration paiement par carte (Stripe ou similaire)
        return ['status' => 'pending', 'external_ref' => null, 'redirect_url' => null];
    }
    public function verifyPayment(string $externalRef): array {
        return ['status' => 'pending', 'montant' => 0];
    }
    public function processWebhook(array $payload): array {
        return ['status' => 'succes', 'external_ref' => $payload['id'] ?? null];
    }
}
