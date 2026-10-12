<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Djomy\DjomyService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

class WalletTopupTest extends TestCase
{
    use RefreshDatabase;

    private function fakeDjomy(bool $success = true): void
    {
        $this->mock(DjomyService::class, function (MockInterface $mock) use ($success) {
            $mock->shouldReceive('initiateGatewayPayment')->andReturn(
                $success
                    ? ['success' => true, 'data' => ['transactionId' => 'tx-top-1', 'redirectUrl' => 'https://pay.djomy.test/x']]
                    : ['success' => false, 'error' => ['message' => 'Numéro invalide']]
            );
            $mock->shouldReceive('verifyWebhookSignature')->andReturn(true);
        });
    }

    private function webhook(string $reference, string $status, string $tx = 'tx-top-1')
    {
        return $this->postJson('/api/v1/payments/djomy/webhook', [
            'eventType' => 'payment.event',
            'data' => ['transactionId' => $tx, 'merchantPaymentReference' => $reference, 'status' => $status],
        ], ['X-Webhook-Signature' => 'v1:ok']);
    }

    public function test_topup_requires_authentication_and_a_sensible_amount(): void
    {
        $this->postJson('/api/v1/wallet/topup', ['montant' => 5000])->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create());
        $this->postJson('/api/v1/wallet/topup', ['montant' => 500, 'payer_phone' => '622000000'])->assertUnprocessable()->assertJsonValidationErrors('montant');
        $this->postJson('/api/v1/wallet/topup', ['montant' => 99999999, 'payer_phone' => '622000000'])->assertUnprocessable()->assertJsonValidationErrors('montant');
        $this->postJson('/api/v1/wallet/topup', ['montant' => 5000])->assertUnprocessable()->assertJsonValidationErrors('payer_phone');
        $this->assertSame(0, Payment::count());
    }

    public function test_topup_creates_a_pending_payment_without_order_and_returns_the_payment_page(): void
    {
        $this->fakeDjomy();
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/v1/wallet/topup', ['montant' => 20000, 'payer_phone' => '622000000'])
            ->assertCreated()
            ->assertJsonPath('redirect_url', 'https://pay.djomy.test/x');

        $payment = Payment::where('reference', $response->json('payment.reference'))->firstOrFail();
        $this->assertNull($payment->order_id);
        $this->assertSame($user->id, $payment->user_id);
        $this->assertSame('en_attente', $payment->status->value);
        $this->assertEquals(20000, $payment->montant);
        $this->assertSame(0, (int) ($user->wallet?->balance ?? 0)); // rien n'est crédité avant la confirmation
    }

    public function test_a_failed_initiation_is_reported_and_credits_nothing(): void
    {
        $this->fakeDjomy(false);
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/wallet/topup', ['montant' => 20000, 'payer_phone' => '622000000'])->assertUnprocessable();

        $this->assertSame('echoue', Payment::first()->status->value);
        $this->assertSame(0, Transaction::count());
    }

    public function test_the_confirmed_topup_credits_the_wallet_exactly_once(): void
    {
        $this->fakeDjomy();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $reference = $this->postJson('/api/v1/wallet/topup', ['montant' => 20000, 'payer_phone' => '622000000'])->json('payment.reference');

        $this->webhook($reference, 'SUCCESS')->assertOk();
        $this->webhook($reference, 'SUCCESS')->assertOk(); // notification rejouée

        $this->assertEquals(20000, Wallet::where('user_id', $user->id)->value('balance'));
        $this->assertSame(1, Transaction::where('source', 'paiement')->where('type', 'credit')->count());
        $this->assertSame('succes', Payment::where('reference', $reference)->first()->status->value);
    }

    public function test_a_failed_or_cancelled_topup_credits_nothing(): void
    {
        $this->fakeDjomy();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $reference = $this->postJson('/api/v1/wallet/topup', ['montant' => 20000, 'payer_phone' => '622000000'])->json('payment.reference');

        $this->webhook($reference, 'FAILED')->assertOk();

        $this->assertSame(0, (int) Wallet::where('user_id', $user->id)->value('balance'));
        $this->assertSame(0, Transaction::count());
    }

    public function test_only_the_owner_can_follow_a_topup_payment(): void
    {
        $this->fakeDjomy();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $reference = $this->postJson('/api/v1/wallet/topup', ['montant' => 20000, 'payer_phone' => '622000000'])->json('payment.reference');

        $this->getJson("/api/v1/payments/{$reference}")->assertOk()->assertJsonPath('data.status', 'en_attente');

        Sanctum::actingAs(User::factory()->create());
        $this->getJson("/api/v1/payments/{$reference}")->assertForbidden();
    }
}
