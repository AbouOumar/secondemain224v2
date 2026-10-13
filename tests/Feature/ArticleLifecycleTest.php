<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ArticleLifecycleTest extends TestCase
{
    use RefreshDatabase;

    private function article(User $seller, array $over = []): Article
    {
        $category = Category::firstOrCreate(['slug' => 'test'], ['libelle' => 'Test', 'icon' => 'bx-box']);

        return Article::create(array_merge([
            'user_id' => $seller->id, 'category_id' => $category->id, 'titre' => 'Annonce', 'slug' => 'a-' . uniqid(),
            'description' => 'd', 'prix' => 1000, 'currency' => 'GNF', 'localisation' => 'Conakry',
            'with_delivery' => false, 'is_published' => true,
        ], $over));
    }

    public function test_an_unpublished_article_is_only_visible_to_its_owner(): void
    {
        $owner = User::factory()->create(['role' => 'vendeur']);
        $hidden = $this->article($owner, ['is_published' => false]);

        $this->getJson('/api/v1/articles/' . $hidden->slug)->assertNotFound();

        Sanctum::actingAs(User::factory()->create());
        $this->getJson('/api/v1/articles/' . $hidden->slug)->assertNotFound();

        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/articles/' . $hidden->slug)->assertOk();
    }

    public function test_the_owner_opening_their_own_article_does_not_add_a_view(): void
    {
        $owner = User::factory()->create(['role' => 'vendeur']);
        $article = $this->article($owner);

        $this->getJson('/api/v1/articles/' . $article->slug)->assertOk();
        $this->assertSame(1, (int) $article->fresh()->vue_count);

        Sanctum::actingAs($owner);
        $this->getJson('/api/v1/articles/' . $article->slug)->assertOk();
        $this->assertSame(1, (int) $article->fresh()->vue_count);
    }

    public function test_photos_can_be_removed_by_the_owner_only(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'vendeur']);
        $article = $this->article($owner);
        $image = $article->images()->create(['url' => UploadedFile::fake()->image('a.jpg')->store('articles', 'public'), 'ordre' => 0]);
        $other = $this->article($owner);
        $foreign = $other->images()->create(['url' => UploadedFile::fake()->image('b.jpg')->store('articles', 'public'), 'ordre' => 0]);

        Sanctum::actingAs(User::factory()->create(['role' => 'vendeur']));
        $this->deleteJson("/api/v1/articles/{$article->id}/images/{$image->id}")->assertForbidden();

        Sanctum::actingAs($owner);
        $this->deleteJson("/api/v1/articles/{$article->id}/images/{$foreign->id}")->assertNotFound(); // photo d'une autre annonce
        $this->deleteJson("/api/v1/articles/{$article->id}/images/{$image->id}")->assertNoContent();

        $this->assertSame(0, $article->images()->count());
        $this->assertSame(1, $other->images()->count());
    }

    public function test_an_article_cannot_end_up_with_more_than_five_photos(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'vendeur']);
        $article = $this->article($owner);
        foreach (range(1, 4) as $i) {
            $article->images()->create(['url' => UploadedFile::fake()->image("p{$i}.jpg")->store('articles', 'public'), 'ordre' => $i]);
        }
        Sanctum::actingAs($owner);

        // 4 + 2 = 6 : refusé, rien n'est ajouté
        $this->post("/api/v1/articles/{$article->id}", [
            '_method' => 'PUT',
            'images' => [UploadedFile::fake()->image('n1.jpg'), UploadedFile::fake()->image('n2.jpg')],
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('images');
        $this->assertSame(4, $article->images()->count());

        // 4 + 1 = 5 : accepté
        $this->post("/api/v1/articles/{$article->id}", [
            '_method' => 'PUT',
            'images' => [UploadedFile::fake()->image('n1.jpg')],
        ], ['Accept' => 'application/json'])->assertOk();
        $this->assertSame(5, $article->images()->count());
    }

    public function test_an_article_can_be_created_with_five_photos_but_not_six(): void
    {
        Storage::fake('public');
        $seller = User::factory()->create(['role' => 'vendeur']);
        $category = Category::firstOrCreate(['slug' => 'test'], ['libelle' => 'Test', 'icon' => 'bx-box']);
        Sanctum::actingAs($seller);

        $payload = fn (int $count) => [
            'titre' => 'Velo', 'description' => 'd', 'prix' => 1000, 'category_id' => $category->id, 'localisation' => 'Conakry', 'etat' => 'bon',
            'images' => array_map(fn ($i) => UploadedFile::fake()->image("p{$i}.jpg"), range(1, $count)),
        ];

        $this->post('/api/v1/articles', $payload(6), ['Accept' => 'application/json'])
            ->assertUnprocessable()->assertJsonValidationErrors('images');
        $this->assertSame(0, Article::count());

        $this->post('/api/v1/articles', $payload(5), ['Accept' => 'application/json'])->assertCreated();
        $this->assertSame(5, Article::firstOrFail()->images()->count());
    }

    public function test_creating_an_article_without_a_condition_is_a_validation_error_not_a_server_error(): void
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $category = Category::firstOrCreate(['slug' => 'test'], ['libelle' => 'Test', 'icon' => 'bx-box']);
        Sanctum::actingAs($seller);

        $this->postJson('/api/v1/articles', [
            'titre' => 'Velo', 'description' => 'd', 'prix' => 1000, 'category_id' => $category->id, 'localisation' => 'Conakry',
        ])->assertUnprocessable()->assertJsonValidationErrors('etat');
    }

    public function test_an_article_can_be_marked_sold_and_put_back_on_sale(): void
    {
        $owner = User::factory()->create(['role' => 'vendeur']);
        $article = $this->article($owner);
        Sanctum::actingAs($owner);

        $this->putJson("/api/v1/articles/{$article->id}", ['statut' => 'vendu'])->assertOk()->assertJsonPath('data.statut', 'vendu');
        $this->assertSame('vendu', $article->fresh()->statut);
        $this->assertCount(0, $this->getJson('/api/v1/articles')->json('data'));

        $this->putJson("/api/v1/articles/{$article->id}", ['statut' => 'en_vente'])->assertOk();
        $this->assertCount(1, $this->getJson('/api/v1/articles')->json('data'));

        $this->putJson("/api/v1/articles/{$article->id}", ['statut' => 'nimporte'])->assertUnprocessable();
    }

    public function test_the_ids_of_all_saved_articles_come_in_one_call(): void
    {
        $user = User::factory()->create();
        $owner = User::factory()->create(['role' => 'vendeur']);
        $ids = [];
        for ($i = 0; $i < 15; $i++) {
            $article = $this->article($owner);
            $user->savedArticles()->attach($article->id);
            $ids[] = $article->id;
        }
        Sanctum::actingAs($user);

        $result = $this->getJson('/api/v1/profile/saved-ids')->assertOk()->json('data');

        $this->assertEqualsCanonicalizing($ids, $result);
    }
}
