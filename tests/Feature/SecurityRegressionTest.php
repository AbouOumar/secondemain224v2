<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_create_web_order(): void
    {
        $article = $this->createArticle();

        $this->post(route('orders.create', ['article' => $article->id, 'delivery' => 1]))
            ->assertRedirect(route('login'));

        $this->assertDatabaseCount('orders', 0);
    }

    public function test_subscription_and_verification_routes_require_authentication(): void
    {
        $this->getJson('/api/v1/subscription/current')->assertUnauthorized();
        $this->getJson('/api/v1/verification/status')->assertUnauthorized();
    }

    public function test_user_cannot_initiate_payment_for_another_users_order(): void
    {
        $order = $this->createOrder();
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser, 'sanctum')
            ->postJson('/api/v1/payments/initiate', [
                'order_id' => $order->id,
                'methode' => 'orange_money',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('payments', 0);
    }

    public function test_user_cannot_view_another_users_payment(): void
    {
        $order = $this->createOrder();
        $payment = Payment::create([
            'reference' => 'PAY-TEST123',
            'order_id' => $order->id,
            'user_id' => $order->buyer_id,
            'montant' => $order->total,
            'currency' => 'GNF',
            'methode' => 'orange_money',
            'status' => 'en_attente',
        ]);
        $otherUser = User::factory()->create();

        $this->actingAs($otherUser, 'sanctum')
            ->getJson('/api/v1/payments/'.$payment->reference)
            ->assertForbidden();
    }

    public function test_order_cancel_uses_reference_route_parameter(): void
    {
        $order = $this->createOrder();

        $this->actingAs($order->buyer, 'sanctum')
            ->postJson('/api/v1/orders/'.$order->reference.'/cancel', [
                'raison' => 'Changed mind',
            ])
            ->assertOk();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'annule',
            'annule_raison' => 'Changed mind',
        ]);
    }

    private function createArticle(): Article
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $category = Category::create([
            'libelle' => 'Motos',
            'slug' => 'motos',
            'icon' => 'bike',
        ]);

        return Article::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'titre' => 'Moto test',
            'slug' => 'moto-test',
            'description' => 'Annonce de test',
            'prix' => 1000000,
            'currency' => 'GNF',
            'localisation' => 'Conakry',
            'etat' => 'bon',
            'with_delivery' => true,
            'delivery_prix' => 10000,
            'is_published' => true,
        ]);
    }

    private function createOrder(): Order
    {
        $article = $this->createArticle();
        $buyer = User::factory()->create(['role' => 'acheteur']);

        return Order::create([
            'reference' => 'CMD-TEST12',
            'buyer_id' => $buyer->id,
            'seller_id' => $article->user_id,
            'article_id' => $article->id,
            'prix_article' => $article->prix,
            'with_delivery' => false,
            'delivery_prix' => 0,
            'total' => $article->prix,
            'status' => 'en_attente_paiement',
        ]);
    }
}
