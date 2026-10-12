<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function pendingOrder(): array
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $buyer = User::factory()->create(['role' => 'acheteur']);
        $category = Category::create(['libelle' => 'Test', 'slug' => 'test', 'icon' => 'bx-box']);
        $article = Article::create([
            'user_id' => $seller->id, 'category_id' => $category->id, 'titre' => 'A', 'slug' => 'a-' . uniqid(),
            'description' => 'd', 'prix' => 50000, 'currency' => 'GNF', 'localisation' => 'Conakry',
            'with_delivery' => false, 'is_published' => true, 'stock' => 1,
        ]);
        $order = Order::create([
            'reference' => 'CMD-SEC001', 'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'article_id' => $article->id,
            'prix_article' => 50000, 'with_delivery' => false, 'delivery_prix' => 0, 'total' => 50000, 'status' => 'en_attente_paiement',
        ]);

        return [$order, $buyer];
    }

    public function test_unimplemented_payment_methods_are_refused(): void
    {
        config(['djomy.client_id' => 'id', 'djomy.client_secret' => 'secret']);
        [$order, $buyer] = $this->pendingOrder();
        Sanctum::actingAs($buyer);

        foreach (['orange_money', 'mtn_momo', 'carte_bancaire', 'portefeuille'] as $method) {
            $this->postJson('/api/v1/payments/initiate', ['order_id' => $order->id, 'methode' => $method])
                ->assertUnprocessable()
                ->assertJsonValidationErrors('methode');
        }
        $this->assertSame(0, Payment::count());
    }

    public function test_placeholder_webhooks_cannot_mark_a_payment_as_paid(): void
    {
        [$order, $buyer] = $this->pendingOrder();
        // Un paiement créé par l'ancienne passerelle factice : aucune référence externe.
        $payment = Payment::create([
            'reference' => 'PAY-X', 'order_id' => $order->id, 'user_id' => $buyer->id, 'montant' => 50000,
            'currency' => 'GNF', 'methode' => 'orange_money', 'status' => 'en_attente', 'external_ref' => null,
        ]);

        // Routes supprimées : rien à appeler, et aucun paiement ne peut être marqué « payé » par ce biais.
        $this->postJson('/api/v1/payments/webhook/orange-money', [])->assertNotFound();
        $this->postJson('/api/v1/payments/webhook/mtn-momo', [])->assertNotFound();

        $this->assertSame('en_attente', $payment->fresh()->status->value);
        $this->assertSame('en_attente_paiement', $order->fresh()->status->value);
    }

    public function test_djomy_webhook_requires_a_valid_signature(): void
    {
        config(['djomy.client_id' => 'id', 'djomy.client_secret' => 'secret']);
        [$order, $buyer] = $this->pendingOrder();
        $payment = Payment::create([
            'reference' => 'PAY-D', 'order_id' => $order->id, 'user_id' => $buyer->id, 'montant' => 50000,
            'currency' => 'GNF', 'methode' => 'djomy', 'status' => 'en_attente', 'external_ref' => 'tx-1',
        ]);
        $body = ['eventType' => 'payment.success', 'data' => ['transactionId' => 'tx-1', 'merchantPaymentReference' => 'PAY-D', 'status' => 'SUCCESS']];

        $this->postJson('/api/v1/payments/djomy/webhook', $body)->assertStatus(401);
        $this->postJson('/api/v1/payments/djomy/webhook', $body, ['X-Webhook-Signature' => 'v1:forgee'])->assertStatus(401);
        $this->assertSame('en_attente', $payment->fresh()->status->value);

        $raw = json_encode($body);
        $signature = 'v1:' . hash_hmac('sha256', $raw, 'secret');
        $this->call('POST', '/api/v1/payments/djomy/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
        ], $raw)->assertOk();

        $this->assertSame('succes', $payment->fresh()->status->value);
        $this->assertSame('paye', $order->fresh()->status->value);
    }

    public function test_djomy_webhook_is_refused_when_the_secret_is_not_configured(): void
    {
        config(['djomy.client_id' => 'id', 'djomy.client_secret' => '']);
        $raw = json_encode(['eventType' => 'x', 'data' => ['status' => 'SUCCESS']]);
        // Une clé vide permettrait à n'importe qui de calculer une signature valide.
        $signature = 'v1:' . hash_hmac('sha256', $raw, '');

        $this->call('POST', '/api/v1/payments/djomy/webhook', [], [], [], [
            'CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json', 'HTTP_X_WEBHOOK_SIGNATURE' => $signature,
        ], $raw)->assertStatus(401);
    }
}
