<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleSortAndPartnerFilterTest extends TestCase
{
    use RefreshDatabase;

    private function article(User $seller, string $titre, int $prix, array $extra = []): Article
    {
        $category = Category::firstOrCreate(['libelle' => 'Test'], ['slug' => 'test', 'icon' => 'bx-box']);

        return Article::create(array_merge([
            'user_id' => $seller->id, 'category_id' => $category->id, 'titre' => $titre, 'slug' => 'a-'.uniqid(),
            'description' => 'd', 'prix' => $prix, 'currency' => 'GNF', 'localisation' => 'Conakry',
            'with_delivery' => false, 'is_published' => true,
        ], $extra));
    }

    private function titles(string $query): array
    {
        return collect($this->getJson('/api/v1/articles'.$query)->assertOk()->json('data'))->pluck('titre')->all();
    }

    public function test_default_order_is_boosted_then_newest(): void
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $old = $this->article($seller, 'Ancien', 100);
        $old->forceFill(['created_at' => now()->subDays(3)])->save();
        $this->article($seller, 'Recent', 300);
        $this->article($seller, 'Booste', 200, ['is_boosted' => true]);

        $this->assertSame(['Booste', 'Recent', 'Ancien'], $this->titles(''));
        $this->assertSame(['Booste', 'Recent', 'Ancien'], $this->titles('?sort=recent'));
    }

    public function test_it_sorts_by_price(): void
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $this->article($seller, 'Moyen', 200);
        $this->article($seller, 'Cher', 300, ['is_boosted' => true]);
        $this->article($seller, 'Pas cher', 100);

        $this->assertSame(['Pas cher', 'Moyen', 'Cher'], $this->titles('?sort=price_asc'));
        $this->assertSame(['Cher', 'Moyen', 'Pas cher'], $this->titles('?sort=price_desc'));
    }

    public function test_an_unknown_sort_falls_back_to_the_default_order(): void
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $this->article($seller, 'A', 100);

        $this->assertSame(['A'], $this->titles('?sort=drop+table'));
    }

    public function test_partner_filter_keeps_only_verified_shop_listings(): void
    {
        $pro = User::factory()->create(['role' => 'revendeur_pro']);
        Partner::create(['user_id' => $pro->id, 'nom_magasin' => 'Boutique verifiee', 'slug' => 'ok', 'adresse' => 'Matam', 'is_verified' => true]);
        $unverified = User::factory()->create(['role' => 'revendeur_pro']);
        Partner::create(['user_id' => $unverified->id, 'nom_magasin' => 'En attente', 'slug' => 'wait', 'adresse' => 'Matam', 'is_verified' => false]);
        $regular = User::factory()->create(['role' => 'vendeur']);

        $this->article($pro, 'Chez la boutique', 100, ['is_verified' => true]);
        $this->article($unverified, 'Boutique non verifiee', 100, ['is_verified' => true]);
        $this->article($regular, 'Particulier', 100, ['is_verified' => true]);

        $this->assertSame(['Chez la boutique'], $this->titles('?partner=1'));
        $this->assertCount(3, $this->titles(''));
    }
}
