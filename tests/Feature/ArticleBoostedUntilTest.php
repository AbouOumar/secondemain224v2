<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleBoostedUntilTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_exposes_the_end_of_its_boost(): void
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $category = Category::create(['libelle' => 'Test', 'slug' => 'test-' . uniqid(), 'icon' => 'bx-box']);
        $article = Article::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'titre' => 'Article booste',
            'slug' => 'article-' . uniqid(),
            'description' => 'Description',
            'prix' => 1000,
            'currency' => 'GNF',
            'localisation' => 'Conakry',
            'with_delivery' => false,
            'is_published' => true,
            'is_boosted' => true,
            'boosted_until' => now()->addDay(),
        ]);

        $this->getJson('/api/v1/articles/' . $article->slug)
            ->assertOk()
            ->assertJsonPath('data.is_boosted', true)
            ->assertJsonStructure(['data' => ['boosted_until']])
            ->assertJsonPath('data.boosted_until', fn ($value) => $value !== null);
    }
}
