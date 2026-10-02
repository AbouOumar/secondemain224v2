<?php

namespace App\Services\Notification;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Envoi de notifications push via Firebase Cloud Messaging (API HTTP v1).
 */
class FcmClient
{
    public const SENT = 'sent';
    public const INVALID_TOKEN = 'invalid';
    public const FAILED = 'failed';

    public function isConfigured(): bool
    {
        return config('firebase.project_id') && is_file((string) config('firebase.credentials'));
    }

    /**
     * @param array<string, string> $data valeurs texte uniquement (exigence FCM)
     */
    public function send(string $deviceToken, string $title, string $body, array $data = []): string
    {
        $response = Http::withToken($this->accessToken())
            ->post('https://fcm.googleapis.com/v1/projects/'.config('firebase.project_id').'/messages:send', [
                'message' => [
                    'token' => $deviceToken,
                    'notification' => ['title' => $title, 'body' => $body],
                    'data' => array_map('strval', $data),
                    'android' => ['priority' => 'high'],
                ],
            ]);

        if ($response->successful()) {
            return self::SENT;
        }

        $errorCode = collect($response->json('error.details', []))->pluck('errorCode')->filter()->first();
        if ($response->status() === 404 || $errorCode === 'UNREGISTERED' || $errorCode === 'INVALID_ARGUMENT') {
            return self::INVALID_TOKEN;
        }

        Log::warning('Échec envoi push', ['status' => $response->status(), 'body' => $response->body()]);

        return self::FAILED;
    }

    /**
     * Jeton OAuth Google (valable 1 h, mis en cache 55 min ; texte seulement dans le cache).
     */
    private function accessToken(): string
    {
        return Cache::remember('fcm_access_token', 3300, function () {
            $account = json_decode((string) file_get_contents((string) config('firebase.credentials')), true);
            $now = time();
            $encode = fn (array $part) => rtrim(strtr(base64_encode(json_encode($part)), '+/', '-_'), '=');
            $unsigned = $encode(['alg' => 'RS256', 'typ' => 'JWT']).'.'.$encode([
                'iss' => $account['client_email'],
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => $account['token_uri'],
                'iat' => $now,
                'exp' => $now + 3600,
            ]);
            openssl_sign($unsigned, $signature, $account['private_key'], OPENSSL_ALGO_SHA256);
            $jwt = $unsigned.'.'.rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

            $token = Http::asForm()->post($account['token_uri'], [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ])->json('access_token');

            if (! $token) {
                throw new RuntimeException('Authentification Firebase impossible.');
            }

            return $token;
        });
    }
}
