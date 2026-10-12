<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderDeliveryApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_exposes_its_delivery_to_the_buyer(): void
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $buyer = User::factory()->create(['role' => 'acheteur']);
        $category = Category::create(['libelle' => 'Test', 'slug' => 'test-' . uniqid(), 'icon' => 'bx-box']);
        $article = Article::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'titre' => 'Article livre',
            'slug' => 'article-' . uniqid(),
            'description' => 'Description',
            'prix' => 50000,
            'currency' => 'GNF',
            'localisation' => 'Conakry',
            'with_delivery' => true,
            'delivery_prix' => 5000,
            'is_published' => true,
        ]);

        Sanctum::actingAs($buyer);
        $reference = $this->postJson('/api/v1/orders', [
            'article_id' => $article->id,
            'with_delivery' => true,
            'delivery_adresse' => 'Kaloum, Conakry',
        ])->assertCreated()->json('data.reference');

        $this->getJson('/api/v1/orders/' . $reference)
            ->assertOk()
            ->assertJsonPath('data.with_delivery', true)
            ->assertJsonPath('data.delivery.status', 'en_attente')
            ->assertJsonPath('data.delivery.delivery_adresse', 'Kaloum, Conakry');
    }
}
