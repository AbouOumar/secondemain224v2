<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SellerStatsApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_seller_stats_answers_for_any_authenticated_role(): void
    {
        foreach (['acheteur', 'vendeur', 'revendeur_pro', 'motard'] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role]));

            $this->getJson('/api/v1/seller/stats')
                ->assertOk()
                ->assertJsonStructure(['total', 'en_vente', 'vendus', 'revenus', 'taux_conversion']);
        }
    }

    public function test_seller_stats_counts_listings_and_completed_sales(): void
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $buyer = User::factory()->create(['role' => 'acheteur']);
        $category = Category::create(['libelle' => 'Test', 'slug' => 'test', 'icon' => 'bx-box']);
        $make = fn (string $titre, bool $published) => Article::create([
            'user_id' => $seller->id, 'category_id' => $category->id, 'titre' => $titre, 'slug' => 'a-'.uniqid(),
            'description' => 'd', 'prix' => 1000, 'currency' => 'GNF', 'localisation' => 'Conakry',
            'with_delivery' => false, 'is_published' => $published,
        ]);
        $sold = $make('Vendu', true);
        $make('En vente', true);
        Order::create([
            'reference' => 'CMD-STAT01', 'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'article_id' => $sold->id,
            'prix_article' => 1000, 'with_delivery' => false, 'delivery_prix' => 0, 'total' => 1000, 'status' => 'livre',
        ]);

        Sanctum::actingAs($seller);
        $this->getJson('/api/v1/seller/stats')
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('en_vente', 2)
            ->assertJsonPath('vendus', 1)
            ->assertJsonPath('revenus', 1000);
    }
}
