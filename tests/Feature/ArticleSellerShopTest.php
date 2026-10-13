<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleSellerShopTest extends TestCase
{
    use RefreshDatabase;

    private function makeArticle(User $seller): Article
    {
        $category = Category::firstOrCreate(['libelle' => 'Test'], ['slug' => 'test', 'icon' => 'bx-box']);

        return Article::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'titre' => 'Annonce',
            'slug' => 'annonce-'.uniqid(),
            'description' => 'Description',
            'prix' => 1000,
            'currency' => 'GNF',
            'localisation' => 'Conakry',
            'with_delivery' => false,
            'is_published' => true,
        ]);
    }

    public function test_article_exposes_the_shop_of_a_pro_seller(): void
    {
        $seller = User::factory()->create(['role' => 'revendeur_pro']);
        Partner::create(['user_id' => $seller->id, 'nom_magasin' => 'Chez Awa', 'slug' => 'chez-awa', 'adresse' => 'Conakry', 'is_verified' => true]);
        $article = $this->makeArticle($seller);

        $this->getJson('/api/v1/articles/'.$article->slug)
            ->assertOk()
            ->assertJsonPath('data.user.partner', ['slug' => 'chez-awa', 'nom_magasin' => 'Chez Awa']);
    }

    public function test_article_of_a_regular_seller_has_no_shop(): void
    {
        $article = $this->makeArticle(User::factory()->create(['role' => 'vendeur']));

        $this->getJson('/api/v1/articles/'.$article->slug)
            ->assertOk()
            ->assertJsonPath('data.user.partner', null);
    }

    public function test_listing_does_not_fire_one_query_per_article_for_shops(): void
    {
        $seller = User::factory()->create(['role' => 'revendeur_pro']);
        Partner::create(['user_id' => $seller->id, 'nom_magasin' => 'Chez Awa', 'slug' => 'chez-awa', 'adresse' => 'Conakry', 'is_verified' => true]);
        foreach (range(1, 5) as $i) {
            $this->makeArticle($seller);
        }

        \DB::enableQueryLog();
        $this->getJson('/api/v1/articles')->assertOk();
        $partnerQueries = collect(\DB::getQueryLog())->filter(fn ($q) => str_contains($q['query'], 'from "partners"'))->count();

        $this->assertLessThanOrEqual(1, $partnerQueries);
    }
}
