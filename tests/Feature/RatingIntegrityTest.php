<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RatingIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function order(string $status = 'livre'): Order
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $buyer = User::factory()->create(['role' => 'acheteur']);
        $category = Category::firstOrCreate(['slug' => 'test'], ['libelle' => 'Test', 'icon' => 'bx-box']);
        $article = Article::create([
            'user_id' => $seller->id, 'category_id' => $category->id, 'titre' => 'A', 'slug' => 'a-' . uniqid(),
            'description' => 'd', 'prix' => 1000, 'currency' => 'GNF', 'localisation' => 'Conakry',
            'with_delivery' => true, 'is_published' => true,
        ]);

        return Order::create([
            'reference' => 'CMD-' . strtoupper(substr(uniqid(), -6)), 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'article_id' => $article->id, 'prix_article' => 1000, 'with_delivery' => true, 'delivery_prix' => 0,
            'total' => 1000, 'status' => $status,
        ]);
    }

    private function rate(Order $order, int $ratedId, string $role = 'vendeur', int $stars = 5)
    {
        return $this->postJson('/api/v1/ratings', [
            'rated_id' => $ratedId, 'rating' => $stars, 'role_type' => $role, 'order_id' => $order->id, 'comment' => 'ok',
        ]);
    }

    public function test_a_buyer_rates_the_seller_of_a_delivered_order_once(): void
    {
        $order = $this->order();
        Sanctum::actingAs($order->buyer);

        $this->rate($order, $order->seller_id)->assertCreated();
        $this->rate($order, $order->seller_id, 'vendeur', 1)->assertStatus(409);

        $this->assertSame(1, Rating::count());
    }

    public function test_the_seller_can_rate_the_buyer_on_the_same_order(): void
    {
        $order = $this->order();
        Sanctum::actingAs($order->seller);

        $this->rate($order, $order->buyer_id, 'acheteur')->assertCreated();
    }

    public function test_a_rating_needs_a_delivered_order(): void
    {
        $order = $this->order('paye');
        Sanctum::actingAs($order->buyer);

        $this->rate($order, $order->seller_id)->assertStatus(422);
        $this->assertSame(0, Rating::count());
    }

    public function test_strangers_cannot_rate_for_an_order(): void
    {
        $order = $this->order();
        Sanctum::actingAs(User::factory()->create());

        $this->rate($order, $order->seller_id)->assertForbidden();
        $this->assertSame(0, Rating::count());
    }

    public function test_the_rated_user_must_be_the_other_party_with_the_matching_role(): void
    {
        $order = $this->order();
        $stranger = User::factory()->create();
        Sanctum::actingAs($order->buyer);

        $this->rate($order, $stranger->id)->assertStatus(422);                       // pas partie de la commande
        $this->rate($order, $order->buyer_id)->assertStatus(422);                    // soi-même
        $this->rate($order, $order->seller_id, 'acheteur')->assertStatus(422);       // mauvais rôle
        $this->assertSame(0, Rating::count());
    }

    public function test_the_buyer_can_rate_the_rider_of_the_delivery(): void
    {
        $order = $this->order();
        $rider = User::factory()->create(['role' => 'motard']);
        Delivery::create([
            'order_id' => $order->id, 'rider_id' => $rider->id, 'pickup_adresse' => 'a', 'pickup_latitude' => 0, 'pickup_longitude' => 0,
            'delivery_adresse' => 'b', 'delivery_latitude' => 0, 'delivery_longitude' => 0, 'prix' => 0, 'status' => 'effectuee',
        ]);
        Sanctum::actingAs($order->buyer);

        $this->rate($order, $rider->id, 'motard')->assertCreated();
    }

    public function test_an_order_is_required(): void
    {
        $order = $this->order();
        Sanctum::actingAs($order->buyer);

        $this->postJson('/api/v1/ratings', ['rated_id' => $order->seller_id, 'rating' => 5, 'role_type' => 'vendeur'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('order_id');
    }
}
