<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleSellerFilterTest extends TestCase
{
    use RefreshDatabase;

    private function makeArticle(User $seller, Category $category, string $titre): Article
    {
        return Article::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'titre' => $titre,
            'slug' => 'article-' . uniqid(),
            'description' => 'Description',
            'prix' => 1000,
            'currency' => 'GNF',
            'localisation' => 'Conakry',
            'with_delivery' => false,
            'is_published' => true,
        ]);
    }

    public function test_articles_can_be_filtered_by_seller(): void
    {
        $category = Category::create(['libelle' => 'Test', 'slug' => 'test-' . uniqid(), 'icon' => 'bx-box']);
        $alice = User::factory()->create(['role' => 'vendeur']);
        $bob = User::factory()->create(['role' => 'vendeur']);
        $this->makeArticle($alice, $category, 'Velo d\'Alice');
        $this->makeArticle($bob, $category, 'Table de Bob');

        $titles = collect($this->getJson('/api/v1/articles?user_id=' . $alice->id)->assertOk()->json('data'))->pluck('titre');

        $this->assertSame(['Velo d\'Alice'], $titles->all());
    }
}
