<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderCreateTest extends TestCase
{
    use RefreshDatabase;

    private function article(bool $withDelivery = true): Article
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $category = Category::create(['libelle' => 'Motos', 'slug' => 'motos', 'icon' => 'bike']);

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
            'with_delivery' => $withDelivery,
            'delivery_prix' => 10000,
            'is_published' => true,
        ]);
    }

    public function test_buyer_can_order_with_delivery(): void
    {
        $article = $this->article();
        $buyer = User::factory()->create(['role' => 'acheteur']);

        $response = $this->actingAs($buyer)
            ->post(route('orders.create', ['article' => $article->id, 'delivery' => 1]));

        $order = Order::firstOrFail();
        $response->assertRedirect(route('payment.show', $order));
        $this->assertTrue($order->with_delivery);
        $this->assertDatabaseCount('deliveries', 1);
    }

    public function test_buyer_can_order_without_delivery(): void
    {
        $article = $this->article();
        $buyer = User::factory()->create(['role' => 'acheteur']);

        $response = $this->actingAs($buyer)
            ->post(route('orders.create', ['article' => $article->id, 'delivery' => 0]));

        $order = Order::firstOrFail();
        $response->assertRedirect(route('payment.show', $order));
        $this->assertDatabaseCount('deliveries', 0);
    }

    public function test_payment_page_renders_for_the_buyer(): void
    {
        $article = $this->article();
        $buyer = User::factory()->create(['role' => 'acheteur']);

        $this->actingAs($buyer)
            ->post(route('orders.create', ['article' => $article->id, 'delivery' => 1]));

        $this->actingAs($buyer)
            ->get(route('payment.show', Order::firstOrFail()))
            ->assertOk();
    }
}
