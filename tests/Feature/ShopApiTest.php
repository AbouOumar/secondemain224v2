<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Order;
use App\Models\Partner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ShopApiTest extends TestCase
{
    use RefreshDatabase;

    private function pro(): User
    {
        return User::factory()->create(['role' => 'revendeur_pro']);
    }

    public function test_guests_and_non_pro_users_cannot_use_the_shop_api(): void
    {
        $this->getJson('/api/v1/shop')->assertUnauthorized();

        Sanctum::actingAs(User::factory()->create(['role' => 'acheteur']));
        $this->getJson('/api/v1/shop')->assertForbidden();
        $this->postJson('/api/v1/shop', ['nom_magasin' => 'X'])->assertForbidden();
    }

    public function test_a_pro_without_shop_gets_a_null_shop(): void
    {
        Sanctum::actingAs($this->pro());

        $this->getJson('/api/v1/shop')->assertOk()->assertJsonPath('data', null);
    }

    public function test_a_pro_creates_then_updates_their_shop(): void
    {
        Storage::fake('public');
        $pro = $this->pro();
        Sanctum::actingAs($pro);

        $this->postJson('/api/v1/shop', [
            'nom_magasin' => 'Chez Awa',
            'adresse' => 'Matam',
            'telephone' => '622000000',
            'horaire' => json_encode([['jour' => 'Lundi', 'ouverture' => '08:00', 'fermeture' => '18:00']]),
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])
            ->assertCreated()
            ->assertJsonPath('data.nom_magasin', 'Chez Awa')
            ->assertJsonPath('data.slug', 'chez-awa-' . $pro->id)
            ->assertJsonPath('data.horaire.0.jour', 'Lundi');

        $this->assertNotNull(Partner::where('user_id', $pro->id)->value('logo'));

        $this->postJson('/api/v1/shop', ['nom_magasin' => 'Deuxieme', 'adresse' => 'Kaloum'])->assertStatus(409);

        $this->putJson('/api/v1/shop', ['nom_magasin' => 'Chez Awa 2', 'adresse' => 'Kaloum'])
            ->assertOk()
            ->assertJsonPath('data.nom_magasin', 'Chez Awa 2')
            ->assertJsonPath('data.adresse', 'Kaloum');
    }

    public function test_the_shop_name_is_required_and_the_slug_unique(): void
    {
        Partner::create(['user_id' => $this->pro()->id, 'nom_magasin' => 'Pris', 'slug' => 'pris', 'adresse' => 'Kaloum']);
        Sanctum::actingAs($this->pro());

        $this->postJson('/api/v1/shop', [])->assertUnprocessable()->assertJsonValidationErrors(['nom_magasin', 'adresse']);
        $this->postJson('/api/v1/shop', ['nom_magasin' => 'Autre', 'adresse' => 'Matam', 'slug' => 'pris'])->assertUnprocessable()->assertJsonValidationErrors('slug');
    }

    public function test_the_dashboard_counts_listings_and_sales(): void
    {
        $pro = $this->pro();
        $buyer = User::factory()->create(['role' => 'acheteur']);
        $category = Category::create(['libelle' => 'Test', 'slug' => 'test', 'icon' => 'bx-box']);
        $make = fn (string $titre, bool $published) => Article::create([
            'user_id' => $pro->id, 'category_id' => $category->id, 'titre' => $titre, 'slug' => 'a-' . uniqid(),
            'description' => 'd', 'prix' => 1000, 'currency' => 'GNF', 'localisation' => 'Conakry',
            'with_delivery' => false, 'is_published' => $published,
        ]);
        $sold = $make('Vendu', true);
        $make('En vente', true);
        $make('Brouillon', false);
        Order::create([
            'reference' => 'CMD-TEST01', 'buyer_id' => $buyer->id, 'seller_id' => $pro->id, 'article_id' => $sold->id,
            'prix_article' => 1000, 'with_delivery' => false, 'delivery_prix' => 0, 'total' => 1000, 'status' => 'livre',
        ]);

        Sanctum::actingAs($pro);
        $this->getJson('/api/v1/shop/dashboard')
            ->assertOk()
            ->assertJsonPath('data.total', 3)
            ->assertJsonPath('data.en_vente', 2)
            ->assertJsonPath('data.vendus', 1)
            ->assertJsonPath('data.taux', 33)
            ->assertJsonPath('data.earnings.ce_mois', 1000)
            ->assertJsonCount(1, 'data.recent_orders');
    }

    public function test_the_public_shop_page_exposes_contact_details(): void
    {
        Partner::create([
            'user_id' => $this->pro()->id, 'nom_magasin' => 'Vitrine', 'slug' => 'vitrine',
            'telephone' => '622111111', 'adresse' => 'Matam',
        ]);

        $this->getJson('/api/v1/partners/vitrine')
            ->assertOk()
            ->assertJsonPath('partner.telephone', '622111111')
            ->assertJsonPath('partner.adresse', 'Matam')
            ->assertJsonPath('partner.user_id', Partner::where('slug', 'vitrine')->value('user_id'));
    }
}
