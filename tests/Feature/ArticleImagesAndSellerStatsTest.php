<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\Category;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArticleImagesAndSellerStatsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function publish(User $seller, array $images): Article
    {
        $category = Category::firstOrCreate(['slug' => 'motos'], ['libelle' => 'Motos', 'icon' => 'bike']);

        $this->actingAs($seller)->post('/articles', [
            'titre' => 'Moto Yamaha',
            'description' => 'Très bonne moto',
            'prix' => 1500000,
            'category_id' => $category->id,
            'localisation' => 'Kaloum',
            'images' => $images,
        ])->assertRedirect();

        return Article::latest('id')->firstOrFail();
    }

    public function test_uploaded_photos_are_resized_to_webp_with_a_thumbnail(): void
    {
        $article = $this->publish(User::factory()->create(), [UploadedFile::fake()->image('photo.jpg', 3000, 2000)]);

        $image = $article->images()->firstOrFail();
        $path = $image->getRawOriginal('url');
        $this->assertStringEndsWith('.webp', $path);
        $this->assertStringEndsWith('.webp', $image->thumb_path);

        [$width, $height] = getimagesize(Storage::disk('public')->path($path));
        $this->assertSame([1600, 1067], [$width, $height]);
        [$thumbWidth] = getimagesize(Storage::disk('public')->path($image->thumb_path));
        $this->assertSame(480, $thumbWidth);
    }

    public function test_client_file_extension_is_never_reused(): void
    {
        // Une vraie image, mais nommée .html par l'envoyeur (Laravel ne bloque que les .php).
        $real = UploadedFile::fake()->image('photo.jpg');
        $disguised = new UploadedFile($real->getRealPath(), 'piege.html', 'image/jpeg', null, true);
        $article = $this->publish(User::factory()->create(), [$disguised]);

        $this->assertStringEndsWith('.webp', $article->images()->firstOrFail()->getRawOriginal('url'));
        $this->assertSame([], array_filter(Storage::disk('public')->allFiles(), fn ($file) => ! str_ends_with($file, '.webp')));
    }

    public function test_lists_use_thumbnails_and_article_page_uses_full_photo(): void
    {
        $article = $this->publish(User::factory()->create(), [UploadedFile::fake()->image('photo.jpg', 1200, 800)]);
        $image = $article->images()->firstOrFail();

        $this->get('/')->assertSee($image->thumb_url, false);
        $this->get('/articles/'.$article->slug)->assertSee('src="'.$image->url.'"', false);
        $this->assertStringNotContainsString('fit=fill', $this->get('/')->getContent());
    }

    public function test_thumbnail_command_backfills_existing_images(): void
    {
        $article = $this->publish(User::factory()->create(), []);
        $old = UploadedFile::fake()->image('a.jpg', 900, 600);
        Storage::disk('public')->put('articles/ancienne.jpg', file_get_contents($old->getRealPath()));
        ArticleImage::withoutEvents(fn () => ArticleImage::create(['article_id' => $article->id, 'url' => 'articles/ancienne.jpg', 'ordre' => 0]));
        ArticleImage::withoutEvents(fn () => ArticleImage::create(['article_id' => $article->id, 'url' => 'articles/absente.jpg', 'ordre' => 1]));

        $this->artisan('images:thumbnails')->expectsOutputToContain('1 vignette(s) créée(s), 1 image(s) introuvable(s)')->assertSuccessful();

        Storage::disk('public')->assertExists('articles/thumbs/ancienne.webp');
        Storage::disk('public')->assertExists('articles/ancienne.jpg');
    }

    public function test_article_page_shows_seller_track_record(): void
    {
        $seller = User::factory()->create(['created_at' => '2025-03-10']);
        $article = $this->publish($seller, []);

        $this->get('/articles/'.$article->slug)
            ->assertSee('Nouveau vendeur')
            ->assertSee('Membre depuis mars 2025')
            ->assertDontSee('Répond à');

        foreach ([5, 4] as $note) {
            Rating::create(['rater_id' => User::factory()->create()->id, 'rated_id' => $seller->id, 'rating' => $note, 'role_type' => 'vendeur']);
        }
        cache()->flush();

        $this->get('/articles/'.$article->slug)->assertSee('4,5/5')->assertSee('(2 avis)');
    }

    public function test_article_and_seller_pages_still_work_when_stats_come_from_database_cache(): void
    {
        // En production le cache est en base : il ne restaure pas les objets (dates).
        config(['cache.default' => 'database']);
        $seller = User::factory()->create();
        $article = $this->publish($seller, []);
        \App\Models\Message::create(['sender_id' => User::factory()->create()->id, 'receiver_id' => $seller->id, 'message' => 'Bonjour']);

        foreach ([1, 2] as $visit) { // 2e visite : statistiques lues depuis le cache
            $this->get('/articles/'.$article->slug)->assertOk()->assertSee('Membre depuis');
            $this->get('/vendeur/'.$seller->id)->assertOk();
        }
    }
}
