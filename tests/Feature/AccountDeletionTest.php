<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Offer;
use App\Models\OauthProvider;
use App\Models\Order;
use App\Models\Partner;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AccountDeletionTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attrs = []): User
    {
        return User::factory()->create(array_merge(['role' => 'vendeur', 'password' => Hash::make('secret-123')], $attrs));
    }

    private function article(User $seller, string $titre = 'Annonce'): Article
    {
        $category = Category::firstOrCreate(['slug' => 'test'], ['libelle' => 'Test', 'icon' => 'bx-box']);

        return Article::create([
            'user_id' => $seller->id, 'category_id' => $category->id, 'titre' => $titre, 'slug' => 'a-' . uniqid(),
            'description' => 'd', 'prix' => 1000, 'currency' => 'GNF', 'localisation' => 'Conakry',
            'with_delivery' => false, 'is_published' => true,
        ]);
    }

    private function order(User $buyer, User $seller, string $status): Order
    {
        return Order::create([
            'reference' => 'CMD-' . strtoupper(substr(uniqid(), -6)), 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'article_id' => $this->article($seller)->id, 'prix_article' => 1000, 'with_delivery' => false,
            'delivery_prix' => 0, 'total' => 1000, 'status' => $status,
        ]);
    }

    public function test_the_password_is_required_and_checked(): void
    {
        $user = $this->user();
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/profile')->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->deleteJson('/api/v1/profile', ['password' => 'mauvais'])->assertUnprocessable()->assertJsonValidationErrors('password');
        $this->assertSame('actif', $user->fresh()->status->value);
    }

    public function test_the_account_is_anonymised_and_personal_data_removed(): void
    {
        $user = $this->user(['name' => 'Awa Diallo', 'email' => 'awa@example.com', 'phone' => '622123456']);
        $article = $this->article($user);
        $user->deviceTokens()->create(['token' => 'tok', 'platform' => 'android']);
        $user->savedArticles()->attach($article->id);
        Partner::create(['user_id' => $user->id, 'nom_magasin' => 'Boutique', 'slug' => 'boutique', 'adresse' => 'Matam']);
        $user->createToken('auth-token');
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/profile', ['password' => 'secret-123'])->assertOk();

        $fresh = $user->fresh();
        $this->assertSame('Compte supprimé', $fresh->name);
        $this->assertNull($fresh->email);
        $this->assertNotSame('622123456', $fresh->phone);
        $this->assertSame('suspendu', $fresh->status->value);
        $this->assertFalse(Hash::check('secret-123', $fresh->password));
        $this->assertSame(0, $user->deviceTokens()->count());
        $this->assertSame(0, $user->savedArticles()->count());
        $this->assertSame(0, Partner::where('user_id', $user->id)->count());
        $this->assertSame(0, $user->tokens()->count());
        $this->assertFalse((bool) $article->fresh()->is_published);
    }

    public function test_the_profile_tells_whether_a_password_is_needed(): void
    {
        $withPassword = $this->user();
        Sanctum::actingAs($withPassword);
        $this->getJson('/api/v1/profile')->assertOk()->assertJsonPath('data.needs_password', true);

        $google = $this->user();
        OauthProvider::create(['user_id' => $google->id, 'provider' => 'google', 'provider_id' => 'g-2']);
        Sanctum::actingAs($google);
        $this->getJson('/api/v1/profile')->assertOk()->assertJsonPath('data.needs_password', false);
    }

    public function test_a_google_account_needs_no_password(): void
    {
        $user = $this->user();
        OauthProvider::create(['user_id' => $user->id, 'provider' => 'google', 'provider_id' => 'g-1']);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/profile')->assertOk();

        $this->assertSame('Compte supprimé', $user->fresh()->name);
        $this->assertSame(0, OauthProvider::where('user_id', $user->id)->count());
    }

    public function test_deletion_is_refused_while_the_wallet_holds_money(): void
    {
        $user = $this->user();
        Wallet::create(['user_id' => $user->id, 'balance' => 5000, 'currency' => 'GNF']);
        Sanctum::actingAs($user);

        $this->deleteJson('/api/v1/profile', ['password' => 'secret-123'])
            ->assertStatus(409)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'portefeuille'));
        $this->assertSame('actif', $user->fresh()->status->value);
    }

    public function test_deletion_is_refused_while_a_paid_order_is_in_progress(): void
    {
        $seller = $this->user();
        $buyer = $this->user();
        $this->order($buyer, $seller, 'paye');

        foreach ([$seller, $buyer] as $party) {
            Sanctum::actingAs($party);
            $this->deleteJson('/api/v1/profile', ['password' => 'secret-123'])->assertStatus(409);
            $this->assertSame('actif', $party->fresh()->status->value);
        }
    }

    public function test_pending_orders_and_offers_are_cancelled(): void
    {
        $seller = $this->user();
        $buyer = $this->user();
        $pending = $this->order($buyer, $seller, 'en_attente_paiement');
        $offer = Offer::create([
            'article_id' => $pending->article_id, 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'montant' => 600, 'made_by' => 'acheteur', 'status' => 'en_attente', 'expires_at' => now()->addDay(),
        ]);
        Sanctum::actingAs($buyer);

        $this->deleteJson('/api/v1/profile', ['password' => 'secret-123'])->assertOk();

        $this->assertSame('annule', $pending->fresh()->status->value);
        $this->assertSame('annulee', $offer->fresh()->status->value);
    }

    public function test_finished_orders_are_kept(): void
    {
        $seller = $this->user();
        $buyer = $this->user();
        $done = $this->order($buyer, $seller, 'livre');
        Sanctum::actingAs($buyer);

        $this->deleteJson('/api/v1/profile', ['password' => 'secret-123'])->assertOk();

        $this->assertSame('livre', $done->fresh()->status->value);
        $this->assertSame($buyer->id, $done->fresh()->buyer_id);
    }
}
