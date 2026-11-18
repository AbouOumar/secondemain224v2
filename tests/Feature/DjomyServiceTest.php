<?php

namespace Tests\Feature;

use App\Services\Djomy\DjomyService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class DjomyServiceTest extends TestCase
{
    public function test_authentication_sends_a_json_object_and_a_signed_api_key(): void
    {
        Cache::forget('djomy_access_token');
        Http::fake([
            '*/v1/auth' => Http::response(['data' => ['accessToken' => 'jeton-de-test']]),
        ]);

        $this->assertSame('jeton-de-test', app(DjomyService::class)->getAccessToken());

        Http::assertSent(function (Request $request) {
            $expectedKey = 'test-client-id:'.hash_hmac('sha256', 'test-client-id', 'test-client-secret');

            return str_ends_with($request->url(), '/v1/auth')
                && $request->body() === '{}' // Djomy rejette « [] » (400)
                && $request->header('X-API-KEY')[0] === $expectedKey;
        });
    }

    public function test_a_refused_authentication_returns_no_token(): void
    {
        Cache::forget('djomy_access_token');
        Http::fake(['*/v1/auth' => Http::response(['errors' => ['Invalid api key']], 401)]);

        $this->assertNull(app(DjomyService::class)->getAccessToken());
    }
}
