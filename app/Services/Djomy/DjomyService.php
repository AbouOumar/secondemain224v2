<?php
namespace App\Services\Djomy;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DjomyService
{
    private string $baseUrl;
    private string $clientId;
    private string $clientSecret;

    public function __construct()
    {
        $this->clientId = config('djomy.client_id');
        $this->clientSecret = config('djomy.client_secret');
        $this->baseUrl = config('djomy.sandbox')
            ? config('djomy.sandbox_url')
            : config('djomy.production_url');
    }

    private function generateSignature(): string
    {
        return hash_hmac('sha256', $this->clientId, $this->clientSecret);
    }

    private function apiKeyHeader(): string
    {
        return $this->clientId . ':' . $this->generateSignature();
    }

    private function client(): \Illuminate\Http\Client\PendingRequest
    {
        $client = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout(8)->connectTimeout(5);

        if (config('djomy.sandbox')) {
            $client->withoutVerifying();
        }

        return $client;
    }

    public function getAccessToken(): ?string
    {
        $cacheKey = 'djomy_access_token';

        try {
            return Cache::remember($cacheKey, now()->addMinutes(55), function () {
                $response = $this->client()
                    ->withHeaders(['X-API-KEY' => $this->apiKeyHeader()])
                    ->post("{$this->baseUrl}/v1/auth", []);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data['data']['accessToken'] ?? null;
                }

                Log::error('Djomy auth failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            });
        } catch (\Exception $e) {
            Log::error('Djomy connection error: ' . $e->getMessage());
            return null;
        }
    }

    private function authHeaders(): array
    {
        return [
            'X-API-KEY' => $this->apiKeyHeader(),
            'Authorization' => 'Bearer ' . $this->getAccessToken(),
        ];
    }

    public function initiatePayment(array $data): array
    {
        if (!$this->getAccessToken()) {
            return $this->serviceUnavailable();
        }

        $payload = [
            'amount' => $data['amount'],
            'countryCode' => $data['country_code'] ?? config('djomy.country_code'),
            'payerIdentifier' => $data['payer_phone'],
            'paymentMethod' => $data['payment_method'],
            'description' => $data['description'] ?? null,
            'merchantPaymentReference' => $data['reference'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ];

        try {
            $response = $this->client()
                ->withHeaders($this->authHeaders())
                ->post("{$this->baseUrl}/v1/payments", array_filter($payload));
            return $this->handleResponse($response, 'initiatePayment');
        } catch (\Exception $e) {
            return $this->serviceUnavailable();
        }
    }

    public function initiateGatewayPayment(array $data): array
    {
        $token = $this->getAccessToken();
        if (!$token) {
            return [
                'success' => false,
                'error' => ['message' => 'Impossible de contacter la plateforme de paiement. Veuillez réessayer plus tard.'],
            ];
        }

        $payload = [
            'amount' => $data['amount'],
            'countryCode' => $data['country_code'] ?? config('djomy.country_code'),
            'payerNumber' => $data['payer_phone'],
            'description' => $data['description'] ?? null,
            'merchantPaymentReference' => $data['reference'] ?? null,
            'returnUrl' => $data['return_url'] ?? null,
            'cancelUrl' => $data['cancel_url'] ?? null,
            'allowedPaymentMethods' => $data['allowed_methods'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ];

        try {
            $response = $this->client()
                ->withHeaders($this->authHeaders())
                ->post("{$this->baseUrl}/v1/payments/gateway", array_filter($payload, fn($v) => $v !== null));
            return $this->handleResponse($response, 'initiateGatewayPayment');
        } catch (\Exception $e) {
            Log::error('Djomi gateway payment error: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => ['message' => 'Le service de paiement est temporairement indisponible.'],
            ];
        }
    }

    public function getPaymentStatus(string $transactionId): array
    {
        if (!$this->getAccessToken()) {
            return $this->serviceUnavailable();
        }

        try {
            $response = $this->client()
                ->withHeaders($this->authHeaders())
                ->get("{$this->baseUrl}/v1/payments/{$transactionId}/status");
            return $this->handleResponse($response, 'getPaymentStatus');
        } catch (\Exception $e) {
            return $this->serviceUnavailable();
        }
    }

    public function confirmOTP(string $transactionReference, string $pin): array
    {
        try {
            $response = $this->client()
                ->withHeaders(['X-API-KEY' => $this->apiKeyHeader()])
                ->post("{$this->baseUrl}/v1/payments/{$transactionReference}/confirmOTP", [
                    'pin' => $pin,
                ]);
            return $this->handleResponse($response, 'confirmOTP');
        } catch (\Exception $e) {
            return $this->serviceUnavailable();
        }
    }

    private function serviceUnavailable(): array
    {
        return ['success' => false, 'error' => ['message' => 'Service de paiement temporairement indisponible.']];
    }

    private function handleResponse($response, string $context): array
    {
        $body = $response->json() ?? [];

        if ($response->successful()) {
            return [
                'success' => true,
                'data' => $body['data'] ?? $body,
                'status' => $response->status(),
            ];
        }

        Log::error("Djomy {$context} failed", [
            'status' => $response->status(),
            'body' => $response->body(),
        ]);

        return [
            'success' => false,
            'error' => $body['error'] ?? ['message' => 'Djomy API error'],
            'status' => $response->status(),
        ];
    }

    public function verifyWebhookSignature(string $payload, string $signatureHeader): bool
    {
        // Format: "v1:<hex_signature>"
        if (!str_starts_with($signatureHeader, 'v1:')) {
            return false;
        }

        $receivedSig = substr($signatureHeader, 3);
        $expectedSig = hash_hmac('sha256', $payload, $this->clientSecret);

        return hash_equals($expectedSig, $receivedSig);
    }
}
