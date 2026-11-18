<?php

namespace Tests\Feature;

use App\Models\Apporteur;
use App\Models\ApporteurCommission;
use App\Models\Article;
use App\Models\Category;
use App\Models\Order;
use App\Models\Partner;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Apporteur\ApporteurService;
use App\Services\Escrow\EscrowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApporteurProgramTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        config([
            'marketplace.commission_rate' => 0.05,
            'marketplace.partner_commission_rate' => 0.10,
            'marketplace.apporteur_share' => 0.30,
        ]);
        $this->admin = User::factory()->create(['role' => 'admin']);
    }

    private function activeApporteur(?User $user = null): Apporteur
    {
        $user ??= User::factory()->create(['role' => 'motard']);
        $service = app(ApporteurService::class);
        $apporteur = $service->apply($user, ['quartier' => 'Gbessia']);
        $service->approve($apporteur, $this->admin);

        return $apporteur->fresh();
    }

    private function article(User $seller, int $prix, bool $published = true): Article
    {
        $category = Category::firstOrCreate(['slug' => 'test'], ['libelle' => 'Test', 'icon' => 'bx-box']);

        return Article::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'titre' => 'Article',
            'slug' => 'article-'.uniqid(),
            'description' => 'Description',
            'prix' => $prix,
            'currency' => 'GNF',
            'localisation' => 'Conakry',
            'is_published' => $published,
        ]);
    }

    /** Commande payée puis séquestre libéré : une vente réussie du vendeur. */
    private function completeSale(User $seller, int $prix): Order
    {
        $buyer = User::factory()->create(['role' => 'acheteur']);
        $order = Order::create([
            'reference' => 'CMD-'.strtoupper(uniqid()),
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'article_id' => $this->article($seller, $prix)->id,
            'prix_article' => $prix,
            'with_delivery' => false,
            'delivery_prix' => 0,
            'total' => $prix,
            'status' => 'paye',
        ]);

        $escrow = app(EscrowService::class);
        $escrow->release($escrow->hold($order), 'confirmation_acheteur');

        return $order;
    }

    private function balance(User $user): int
    {
        return (int) Wallet::where('user_id', $user->id)->value('balance');
    }

    public function test_application_is_reviewed_and_an_easy_code_is_given_on_approval(): void
    {
        $user = User::factory()->create(['role' => 'vendeur']);

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/apporteur', ['quartier' => 'Kaloum', 'activite' => 'Étudiant'])
            ->assertCreated()->assertJsonPath('data.status', 'en_attente')->assertJsonPath('data.code', null);
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/apporteur', ['quartier' => 'Kaloum'])->assertStatus(409);
        $this->assertDatabaseHas('notifications', ['user_id' => $this->admin->id, 'type' => 'admin.apporteur_demande']);

        $apporteur = Apporteur::where('user_id', $user->id)->first();
        $this->actingAs($this->admin)->post(route('admin.apporteurs.approve', $apporteur))->assertRedirect();

        $apporteur->refresh();
        $this->assertSame('actif', $apporteur->status);
        $this->assertMatchesRegularExpression('/^[A-HJKMNP-Z2-9]{6}$/', $apporteur->code);
        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'type' => 'apporteur_traite']);
        $this->assertSame('vendeur', $user->fresh()->role->value); // le rôle du compte ne change pas
    }

    public function test_a_seller_registering_with_the_code_is_linked_to_the_apporteur(): void
    {
        $apporteur = $this->activeApporteur();

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Mariama', 'phone' => '622000222', 'password' => 'secret-123', 'password_confirmation' => 'secret-123',
            'role' => 'vendeur', 'referral_code' => strtolower($apporteur->code),
        ])->assertCreated();

        $seller = User::where('phone', '622000222')->first();
        $this->assertSame($apporteur->id, $seller->apporteur_id);
        $this->assertNotNull($seller->referred_at);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Ibrahima', 'phone' => '622000333', 'password' => 'secret-123', 'password_confirmation' => 'secret-123',
            'referral_code' => 'XXXXXX',
        ])->assertUnprocessable()->assertJsonValidationErrors('referral_code');
    }

    public function test_web_registration_link_prefills_the_code_and_links_the_account(): void
    {
        $apporteur = $this->activeApporteur();

        $this->get('/register?ref='.$apporteur->code)->assertOk()->assertSee('value="'.$apporteur->code.'"', false);

        $this->post('/register', [
            'name' => 'Fatou', 'email' => 'fatou@example.com', 'phone' => '+224622000444',
            'password' => 'secret-123', 'password_confirmation' => 'secret-123',
            'role' => 'vendeur', 'referral_code' => $apporteur->code,
        ])->assertRedirect();

        $this->assertSame($apporteur->id, User::where('email', 'fatou@example.com')->value('apporteur_id'));
    }

    public function test_the_apporteur_earns_30_percent_of_the_commission_on_the_first_sale_only(): void
    {
        $apporteur = $this->activeApporteur();
        $seller = User::factory()->create(['role' => 'vendeur']);
        app(ApporteurService::class)->attachReferral($seller, $apporteur->code);

        // Téléphone à 1 500 000 GNF : commission 5 % = 75 000, part de l'apporteur 30 % = 22 500 (exemple du document).
        $order = $this->completeSale($seller, 1500000);

        $this->assertSame(22500, $this->balance($apporteur->user));
        $this->assertSame(1425000, $this->balance($seller));
        $this->assertDatabaseHas('apporteur_commissions', [
            'apporteur_id' => $apporteur->id, 'seller_id' => $seller->id, 'order_id' => $order->id,
            'commission_amount' => 75000, 'amount' => 22500, 'status' => 'verse',
        ]);
        $this->assertDatabaseHas('transactions', ['source' => 'apport', 'montant' => 22500, 'source_id' => $order->id]);
        $this->assertDatabaseHas('notifications', ['user_id' => $apporteur->user_id, 'type' => 'apporteur_gain']);

        // Deuxième vente du même vendeur : rien de plus pour l'apporteur.
        $this->completeSale($seller, 1500000);
        $this->assertSame(22500, $this->balance($apporteur->user));
        $this->assertSame(1, ApporteurCommission::count());
    }

    public function test_releasing_the_same_escrow_twice_pays_the_apporteur_once(): void
    {
        $apporteur = $this->activeApporteur();
        $seller = User::factory()->create(['role' => 'vendeur']);
        app(ApporteurService::class)->attachReferral($seller, $apporteur->code);

        $order = $this->completeSale($seller, 1000000);
        app(EscrowService::class)->release($order->fresh()->escrow, 'rejeu');

        $this->assertSame(15000, $this->balance($apporteur->user));
    }

    public function test_partner_shops_pay_their_own_rate(): void
    {
        $apporteur = $this->activeApporteur();
        $shopOwner = User::factory()->create(['role' => 'revendeur_pro']);
        Partner::create(['user_id' => $shopOwner->id, 'nom_magasin' => 'Meubles Kaloum', 'slug' => 'meubles-kaloum', 'adresse' => 'Kaloum', 'is_verified' => true]);
        app(ApporteurService::class)->attachReferral($shopOwner, $apporteur->code);

        // Canapé à 2 000 000 GNF en magasin à 10 % (taux par défaut) : commission 200 000, apporteur 60 000.
        $order = $this->completeSale($shopOwner, 2000000);

        $this->assertSame(200000, $order->fresh()->escrow->commission_amount);
        $this->assertSame(60000, $this->balance($apporteur->user));
    }

    public function test_commission_rate_depends_on_the_seller(): void
    {
        $escrow = app(EscrowService::class);
        $individual = User::factory()->create();
        $verified = User::factory()->create();
        $custom = User::factory()->create();
        $unverified = User::factory()->create();
        Partner::create(['user_id' => $verified->id, 'nom_magasin' => 'A', 'slug' => 'a', 'adresse' => 'x', 'is_verified' => true]);
        Partner::create(['user_id' => $custom->id, 'nom_magasin' => 'B', 'slug' => 'b', 'adresse' => 'x', 'is_verified' => true, 'commission_rate' => 0.12]);
        Partner::create(['user_id' => $unverified->id, 'nom_magasin' => 'C', 'slug' => 'c', 'adresse' => 'x', 'is_verified' => false, 'commission_rate' => 0.15]);

        $this->assertSame(0.05, $escrow->commissionRateFor($individual->id));
        $this->assertSame(0.10, $escrow->commissionRateFor($verified->id));
        $this->assertSame(0.12, $escrow->commissionRateFor($custom->id));
        $this->assertSame(0.05, $escrow->commissionRateFor($unverified->id));
    }

    public function test_admin_sets_a_shop_rate_within_7_to_15_percent(): void
    {
        $owner = User::factory()->create();
        $partner = Partner::create(['user_id' => $owner->id, 'nom_magasin' => 'A', 'slug' => 'a', 'adresse' => 'x', 'is_verified' => true]);

        $this->actingAs($this->admin)->post(route('admin.partners.commission', $partner), ['rate' => 12.5])->assertSessionHasNoErrors();
        $this->assertSame(0.125, $partner->fresh()->commission_rate);

        $this->actingAs($this->admin)->post(route('admin.partners.commission', $partner), ['rate' => 20])->assertSessionHasErrors('rate');
        $this->actingAs($this->admin)->post(route('admin.partners.commission', $partner), ['rate' => ''])->assertSessionHasNoErrors();
        $this->assertNull($partner->fresh()->commission_rate);
    }

    public function test_a_suspended_apporteur_is_not_paid_and_its_code_is_refused(): void
    {
        $apporteur = $this->activeApporteur();
        $seller = User::factory()->create(['role' => 'vendeur']);
        app(ApporteurService::class)->attachReferral($seller, $apporteur->code);

        $this->actingAs($this->admin)->post(route('admin.apporteurs.suspend', $apporteur), ['note' => 'Fraude'])->assertRedirect();
        $this->completeSale($seller, 1500000);

        $this->assertSame(0, $this->balance($apporteur->user));
        $this->assertDatabaseHas('apporteur_commissions', ['seller_id' => $seller->id, 'status' => 'non_verse']);

        $other = User::factory()->create();
        $this->actingAs($other, 'sanctum')->postJson('/api/v1/apporteur/code', ['code' => $apporteur->code])->assertStatus(422);
    }

    public function test_entering_a_code_later_follows_the_rules(): void
    {
        $apporteur = $this->activeApporteur();
        $seller = User::factory()->create(['role' => 'vendeur']);

        $this->actingAs($apporteur->user, 'sanctum')->postJson('/api/v1/apporteur/code', ['code' => $apporteur->code])
            ->assertStatus(422)->assertJsonPath('message', 'Vous ne pouvez pas utiliser votre propre code.');

        $this->actingAs($seller, 'sanctum')->postJson('/api/v1/apporteur/code', ['code' => $apporteur->code])->assertOk();
        $this->actingAs($seller, 'sanctum')->postJson('/api/v1/apporteur/code', ['code' => $apporteur->code])->assertStatus(409);

        // Un vendeur qui a déjà vendu n'a pas été « apporté ».
        $veteran = User::factory()->create(['role' => 'vendeur']);
        $this->completeSale($veteran, 100000);
        $this->actingAs($veteran, 'sanctum')->postJson('/api/v1/apporteur/code', ['code' => $apporteur->code])
            ->assertStatus(409)->assertJsonPath('message', 'Le code apporteur se saisit avant votre première vente.');
    }

    public function test_the_app_shows_the_code_link_and_earnings(): void
    {
        $apporteur = $this->activeApporteur();
        $seller = User::factory()->create(['role' => 'vendeur', 'name' => 'Aminata']);
        app(ApporteurService::class)->attachReferral($seller, $apporteur->code);
        $this->completeSale($seller, 1500000);

        $this->actingAs($apporteur->user, 'sanctum')->getJson('/api/v1/apporteur')
            ->assertOk()
            ->assertJsonPath('data.apporteur.code', $apporteur->code)
            ->assertJsonPath('data.apporteur.share_link', route('register', ['ref' => $apporteur->code]))
            ->assertJsonPath('data.apporteur.stats.sellers', 1)
            ->assertJsonPath('data.apporteur.stats.active_sellers', 1)
            ->assertJsonPath('data.apporteur.stats.first_sales', 1)
            ->assertJsonPath('data.apporteur.stats.earned', 22500)
            ->assertJsonPath('data.apporteur.recent_gains.0.seller', 'Aminata')
            ->assertJsonPath('data.share_percent', 30);

        $this->actingAs($seller, 'sanctum')->getJson('/api/v1/apporteur')
            ->assertJsonPath('data.apporteur', null)
            ->assertJsonPath('data.referred_by', $apporteur->user->name)
            ->assertJsonPath('data.can_enter_code', false);
    }

    public function test_leaderboard_counts_sellers_who_published_an_ad(): void
    {
        $top = $this->activeApporteur();
        $second = $this->activeApporteur();
        $service = app(ApporteurService::class);

        foreach ([[$top, 3], [$second, 1]] as [$apporteur, $count]) {
            for ($i = 0; $i < $count; $i++) {
                $seller = User::factory()->create(['role' => 'vendeur']);
                $service->attachReferral($seller, $apporteur->code);
                $this->article($seller, 50000);
            }
        }
        // Inscrit mais sans annonce publiée : ne compte pas.
        $empty = User::factory()->create();
        $service->attachReferral($empty, $second->code);
        $this->article($empty, 50000, published: false);

        $board = $service->leaderboard(now()->startOfWeek(), now()->endOfWeek());

        $this->assertSame([$top->id, $second->id], $board->pluck('apporteur.id')->all());
        $this->assertSame([3, 1], $board->pluck('sellers')->all());

        $this->actingAs($this->admin)->get(route('admin.apporteurs.index', ['status' => 'actif']))
            ->assertOk()->assertSee('Classement de la semaine')->assertSee($top->code);
    }

    public function test_profile_page_follows_the_apporteur_lifecycle(): void
    {
        $user = User::factory()->create(['role' => 'vendeur']);

        $this->actingAs($user)->get(route('profile.apporteur'))->assertOk()->assertSee('Devenez apporteur');
        $this->actingAs($user)->post(route('profile.apporteur.apply'), ['quartier' => 'Ratoma'])->assertSessionHas('success');
        $this->actingAs($user)->get(route('profile.apporteur'))->assertSee('Candidature en cours');

        app(ApporteurService::class)->approve($user->apporteurProfile, $this->admin);
        $code = $user->apporteurProfile()->value('code');
        $this->actingAs($user)->get(route('profile.apporteur'))->assertSee($code)->assertSee('Vendeurs apportés');
    }
}
