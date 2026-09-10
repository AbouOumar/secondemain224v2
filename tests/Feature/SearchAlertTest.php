<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\SearchAlert;
use App\Models\User;
use App\Services\Search\SearchAlertService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAlertTest extends TestCase
{
    use RefreshDatabase;

    private function makeCategory(): Category
    {
        return Category::create(['libelle' => 'Test ' . uniqid(), 'slug' => 'test-' . uniqid(), 'icon' => 'bx-box']);
    }

    private function makeArticle(Category $category, int $prix, string $titre = 'Article'): Article
    {
        $seller = User::factory()->create(['role' => 'vendeur']);

        return Article::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'titre' => $titre,
            'slug' => 'article-' . uniqid(),
            'description' => 'Description',
            'prix' => $prix,
            'currency' => 'GNF',
            'localisation' => 'Conakry',
            'with_delivery' => false,
            'is_published' => true,
        ]);
    }

    public function test_alert_notifies_only_new_matching_articles(): void
    {
        $user = User::factory()->create();
        $category = $this->makeCategory();

        // Une annonce déjà présente avant la création de l'alerte : le
        // curseur est initialisé dessus (comme le fait le contrôleur via
        // SearchAlertService::initializeCursor), donc elle ne compte pas
        // comme une "nouveauté".
        $old = $this->makeArticle($category, 50000, 'Ancienne annonce');

        $alert = SearchAlert::create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'min_price' => 10000,
            'max_price' => 100000,
            'is_active' => true,
            'last_matched_article_id' => $old->id,
        ]);

        // Une nouvelle annonce correspondante, créée après l'alerte.
        $newArticle = $this->makeArticle($category, 60000, 'Nouvelle annonce');

        $service = app(SearchAlertService::class);
        $count = $service->checkAndNotify($alert);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $user->id,
            'type' => 'alerte_recherche',
        ]);
        $this->assertSame($newArticle->id, $alert->fresh()->last_matched_article_id);

        // Une seconde vérification sans nouvelle annonce ne renotifie pas.
        $this->assertSame(0, $service->checkAndNotify($alert));
        $this->assertSame(1, \App\Models\Notification::where('user_id', $user->id)->count());
    }

    public function test_alert_respects_price_range_filter(): void
    {
        $user = User::factory()->create();
        $category = $this->makeCategory();

        $alert = SearchAlert::create([
            'user_id' => $user->id,
            'min_price' => 50000,
            'max_price' => 70000,
            'is_active' => true,
        ]);

        $this->makeArticle($category, 30000, 'Trop bas'); // hors plage
        $this->makeArticle($category, 60000, 'Dans la plage');

        $count = app(SearchAlertService::class)->checkAndNotify($alert);

        $this->assertSame(1, $count);
    }
}
