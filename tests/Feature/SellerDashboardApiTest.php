<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SellerDashboardApiTest extends TestCase
{
    use RefreshDatabase;

    private function article(User $seller, string $titre, bool $published = true): Article
    {
        $category = Category::firstOrCreate(['libelle' => 'Test'], ['slug' => 'test', 'icon' => 'bx-box']);

        return Article::create([
            'user_id' => $seller->id, 'category_id' => $category->id, 'titre' => $titre, 'slug' => 'a-'.uniqid(),
            'description' => 'd', 'prix' => 1000, 'currency' => 'GNF', 'localisation' => 'Conakry',
            'with_delivery' => false, 'is_published' => $published,
        ]);
    }

    private function order(User $seller, User $buyer, Article $article, string $status, int $total, ?string $when = null): Order
    {
        $order = Order::create([
            'reference' => 'CMD-'.strtoupper(substr(uniqid(), -6)), 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'article_id' => $article->id, 'prix_article' => $total, 'with_delivery' => false, 'delivery_prix' => 0,
            'total' => $total, 'status' => $status,
        ]);
        if ($when) {
            $order->forceFill(['created_at' => $when])->save();
        }

        return $order;
    }

    public function test_a_regular_seller_gets_counts_earnings_by_period_and_recent_orders(): void
    {
        $this->travelTo(now()->startOfMonth()->addDays(10)->setTime(12, 0));
        $seller = User::factory()->create(['role' => 'vendeur']);
        $buyer = User::factory()->create(['role' => 'acheteur']);
        $sold = $this->article($seller, 'Vendu');
        $this->article($seller, 'En vente');
        $this->article($seller, 'Brouillon', false);

        $this->order($seller, $buyer, $sold, 'livre', 5000);                                   // aujourd'hui
        $this->order($seller, $buyer, $sold, 'paye', 2000, now()->subDays(1)->toDateTimeString()); // ce mois, pas aujourd'hui
        $this->order($seller, $buyer, $sold, 'annule', 9000);                                   // jamais compté

        Sanctum::actingAs($seller);
        $response = $this->getJson('/api/v1/seller/dashboard')->assertOk();

        $response->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.en_vente', 2)
            ->assertJsonPath('data.vendus', 1)
            ->assertJsonPath('data.taux', 33)
            ->assertJsonPath('data.earnings.aujourd_hui', 5000)
            ->assertJsonPath('data.earnings.ce_mois', 7000)
            ->assertJsonPath('data.earnings.cette_annee', 7000)
            ->assertJsonPath('data.earnings.cette_semaine', fn ($value) => $value >= 5000 && $value <= 7000)
            ->assertJsonCount(3, 'data.recent_orders');
    }

    public function test_it_only_shows_the_callers_own_sales(): void
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $other = User::factory()->create(['role' => 'vendeur']);
        $buyer = User::factory()->create(['role' => 'acheteur']);
        $this->order($other, $buyer, $this->article($other, 'Autre'), 'livre', 8000);

        Sanctum::actingAs($seller);
        $this->getJson('/api/v1/seller/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total', 0)
            ->assertJsonPath('data.earnings.cette_annee', 0)
            ->assertJsonCount(0, 'data.recent_orders');
    }

    public function test_it_requires_authentication(): void
    {
        $this->getJson('/api/v1/seller/dashboard')->assertUnauthorized();
    }

    public function test_the_shop_dashboard_uses_the_same_figures(): void
    {
        $pro = User::factory()->create(['role' => 'revendeur_pro']);
        $buyer = User::factory()->create(['role' => 'acheteur']);
        $this->order($pro, $buyer, $this->article($pro, 'Vendu'), 'livre', 3000);

        Sanctum::actingAs($pro);
        $this->getJson('/api/v1/shop/dashboard')
            ->assertOk()
            ->assertJsonPath('data.earnings.cette_semaine', 3000)
            ->assertJsonPath('data.earnings.cette_annee', 3000);
    }
}
