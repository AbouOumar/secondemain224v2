<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SavedToggleTest extends TestCase
{
    use RefreshDatabase;

    private function article(): Article
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
            'is_published' => true,
        ]);
    }

    public function test_post_toggles_the_favorite(): void
    {
        $user = User::factory()->create();
        $article = $this->article();

        $this->actingAs($user)->postJson("/saved/{$article->id}")->assertOk()->assertJson(['saved' => true]);
        $this->assertTrue($user->savedArticles()->where('article_id', $article->id)->exists());

        $this->actingAs($user)->postJson("/saved/{$article->id}")->assertOk()->assertJson(['saved' => false]);
        $this->assertFalse($user->savedArticles()->where('article_id', $article->id)->exists());
    }

    public function test_get_cannot_change_favorites(): void
    {
        $user = User::factory()->create();
        $article = $this->article();

        $this->actingAs($user)->get("/saved/{$article->id}")->assertStatus(405);

        $this->assertFalse($user->savedArticles()->where('article_id', $article->id)->exists());
    }
}
