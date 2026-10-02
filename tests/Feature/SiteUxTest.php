<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ArticleImage;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SiteUxTest extends TestCase
{
    use RefreshDatabase;

    private function createArticle(array $seller = []): Article
    {
        $category = Category::create(['libelle' => 'Motos', 'slug' => 'motos', 'icon' => 'bike']);

        return Article::create([
            'user_id' => User::factory()->create($seller + ['role' => 'vendeur', 'phone' => '620112233'])->id,
            'category_id' => $category->id,
            'titre' => 'Moto Yamaha',
            'slug' => 'moto-yamaha',
            'description' => "Très bonne moto,\npeu servie.",
            'prix' => 1500000,
            'currency' => 'GNF',
            'localisation' => 'Kaloum',
            'etat' => 'bon',
            'with_delivery' => false,
            'is_published' => true,
        ]);
    }

    private function orderFor(Article $article, User $buyer, string $status): void
    {
        Order::create([
            'reference' => 'CMD-'.$status,
            'buyer_id' => $buyer->id,
            'seller_id' => $article->user_id,
            'article_id' => $article->id,
            'prix_article' => $article->prix,
            'with_delivery' => false,
            'delivery_prix' => 0,
            'total' => $article->prix,
            'status' => $status,
        ]);
    }

    public function test_seller_phone_is_hidden_until_a_paid_order(): void
    {
        $article = $this->createArticle();
        $buyer = User::factory()->create();

        $this->get('/articles/moto-yamaha')->assertOk()->assertDontSee('620112233')->assertSee('Son numéro vous sera communiqué');
        $this->actingAs($buyer)->get('/articles/moto-yamaha')->assertDontSee('620112233');

        $this->orderFor($article, $buyer, 'en_attente_paiement');
        $this->actingAs($buyer)->get('/articles/moto-yamaha')->assertDontSee('620112233');

        Order::query()->update(['status' => 'paye']);
        $this->actingAs($buyer)->get('/articles/moto-yamaha')->assertSee('620112233')->assertSee('tel:620112233', false);
    }

    public function test_placeholder_phone_of_google_accounts_is_never_shown(): void
    {
        $article = $this->createArticle(['phone' => 'g_abcdef123456']);
        $buyer = User::factory()->create();
        $this->orderFor($article, $buyer, 'paye');

        $this->actingAs($buyer)->get('/articles/moto-yamaha')->assertDontSee('g_abcdef123456');
        $this->actingAs($article->user, 'sanctum')->getJson('/api/v1/profile')->assertJsonPath('data.phone', null);
    }

    public function test_google_user_without_phone_is_asked_to_complete_profile(): void
    {
        Notification::fake();
        config(['services.google.client_id' => 'id', 'services.google.client_secret' => 'secret']);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'access']),
            'www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => '1', 'email' => 'awa@gmail.com', 'email_verified' => true, 'name' => 'Awa',
            ]),
        ]);

        $this->withSession(['google_oauth_state' => 'etat'])
            ->get('/auth/google/callback?state=etat&code=code')
            ->assertRedirect(route('profile.edit'));

        $user = User::where('email', 'awa@gmail.com')->firstOrFail();
        $this->actingAs($user)->get('/')->assertSee('Ajoutez votre numéro de téléphone');
        $this->actingAs($user)->get('/profile/edit')->assertDontSee($user->phone);

        $this->actingAs($user)->put('/profile/update', ['name' => 'Awa', 'email' => 'awa@gmail.com', 'phone' => '620445566'])
            ->assertRedirect();
        $this->assertTrue($user->fresh()->hasRealPhone());
        $this->actingAs($user->fresh())->get('/')->assertDontSee('Ajoutez votre numéro de téléphone');
    }

    public function test_article_page_has_link_preview_tags(): void
    {
        $article = $this->createArticle();
        ArticleImage::create(['article_id' => $article->id, 'url' => 'articles/moto.jpg', 'ordre' => 0]);

        $this->get('/articles/moto-yamaha')
            ->assertOk()
            ->assertSee('<title>Moto Yamaha — 1 500 000 GNF | '.config('app.name').'</title>', false)
            ->assertSee('<meta property="og:type" content="product">', false)
            ->assertSee('<meta property="og:image" content="'.asset('storage/articles/moto.jpg').'">', false)
            ->assertSee('<meta property="og:description" content="1 500 000 GNF · Kaloum · Très bonne moto, peu servie.">', false)
            ->assertSee('<meta property="product:price:amount" content="1500000">', false);
    }

    public function test_home_page_has_default_description_and_preview_image(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('<meta name="description" content="Achetez et vendez', false)
            ->assertSee('<meta property="og:image" content="'.asset('assets/img/hero-bg.jpg').'">', false);
    }

    public function test_no_placeholder_contact_or_dead_footer_links(): void
    {
        $this->get('/nous')->assertOk()->assertDontSee('XXX')->assertDontSee('secondemain224.com');
        $this->get('/')->assertDontSee('href="#" class="text-white', false);

        config(['legal.public_phone' => '+224 620 00 00 00', 'legal.social.whatsapp' => '224620000000']);
        $this->get('/nous')->assertSee('tel:+224620000000', false);
        $this->get('/')->assertSee('https://wa.me/224620000000', false);
    }

    public function test_titles_and_descriptions_are_escaped_once(): void
    {
        $article = $this->createArticle();
        $article->update(['titre' => '<script>alert(1)</script>', 'description' => "L'été & <b>gras</b>"]);

        $this->get('/articles/moto-yamaha')
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('<title>&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertSee('content="1 500 000 GNF · Kaloum · L&#039;été &amp; gras"', false)
            ->assertDontSee('&amp;#039;', false);
    }
}
