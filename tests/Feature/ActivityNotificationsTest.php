<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\IdentityVerification;
use App\Models\Notification;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Report;
use App\Models\User;
use App\Models\Wallet;
use App\Services\Djomy\DjomyService;
use App\Services\Notification\PushRoute;
use App\Services\Subscription\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification as MailNotification;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Notifications (liste de l'app + push) des paiements, annonces, vérifications,
 * abonnements, signalements et demandes de rôle.
 */
class ActivityNotificationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        MailNotification::fake();
    }

    private function types(User $user): array
    {
        return Notification::where('user_id', $user->id)->orderBy('id')->pluck('type')->all();
    }

    private function article(User $seller, array $over = []): Article
    {
        $category = Category::firstOrCreate(['slug' => 'test'], ['libelle' => 'Test', 'icon' => 'bx-box']);

        return Article::create(array_merge([
            'user_id' => $seller->id, 'category_id' => $category->id, 'titre' => 'Moto', 'slug' => 'moto-'.uniqid(),
            'description' => 'd', 'prix' => 50000, 'currency' => 'GNF', 'localisation' => 'Conakry',
            'with_delivery' => false, 'is_published' => true, 'stock' => 1,
        ], $over));
    }

    private function djomyWebhook(string $reference, string $status)
    {
        $this->mock(DjomyService::class, fn (MockInterface $mock) => $mock->shouldReceive('verifyWebhookSignature')->andReturn(true));

        return $this->postJson('/api/v1/payments/djomy/webhook', [
            'eventType' => 'payment.event',
            'data' => ['transactionId' => 'tx-'.$reference, 'merchantPaymentReference' => $reference, 'status' => $status],
        ], ['X-Webhook-Signature' => 'v1:ok']);
    }

    // --- paiements -----------------------------------------------------------------------------

    public function test_a_confirmed_order_payment_notifies_buyer_and_seller_and_a_sold_out_listing(): void
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $buyer = User::factory()->create(['role' => 'acheteur']);
        $article = $this->article($seller);
        $order = Order::create([
            'reference' => 'CMD-N001', 'buyer_id' => $buyer->id, 'seller_id' => $seller->id, 'article_id' => $article->id,
            'prix_article' => 50000, 'with_delivery' => false, 'delivery_prix' => 0, 'total' => 50000, 'status' => 'en_attente_paiement',
        ]);
        Payment::create(['reference' => 'PAY-N1', 'order_id' => $order->id, 'user_id' => $buyer->id, 'montant' => 50000,
            'currency' => 'GNF', 'methode' => 'djomy', 'status' => 'en_attente', 'external_ref' => 'tx-1']);

        $this->djomyWebhook('PAY-N1', 'SUCCESS')->assertOk();

        $this->assertSame(['paiement.confirme'], $this->types($buyer));
        $this->assertSame(['paiement.recu', 'annonce.vendue'], $this->types($seller));
    }

    public function test_a_wallet_topup_notifies_once_even_if_the_webhook_is_replayed(): void
    {
        $user = User::factory()->create();
        Payment::create(['reference' => 'PAY-T1', 'order_id' => null, 'user_id' => $user->id, 'montant' => 20000,
            'currency' => 'GNF', 'methode' => 'djomy', 'status' => 'en_attente', 'external_ref' => 'tx-PAY-T1']);

        $this->djomyWebhook('PAY-T1', 'SUCCESS')->assertOk();
        $this->djomyWebhook('PAY-T1', 'SUCCESS')->assertOk();

        $this->assertSame(['recharge.recue'], $this->types($user));
    }

    public function test_a_failed_payment_notifies_the_payer(): void
    {
        $user = User::factory()->create();
        Payment::create(['reference' => 'PAY-F1', 'order_id' => null, 'user_id' => $user->id, 'montant' => 20000,
            'currency' => 'GNF', 'methode' => 'djomy', 'status' => 'en_attente', 'external_ref' => 'tx-PAY-F1']);

        $this->djomyWebhook('PAY-F1', 'FAILED')->assertOk();

        $this->assertSame(['paiement.echoue'], $this->types($user));
    }

    public function test_a_boost_notifies_the_seller(): void
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        Wallet::create(['user_id' => $seller->id, 'balance' => 20000, 'currency' => 'GNF']);
        $article = $this->article($seller);
        Sanctum::actingAs($seller);

        $this->postJson("/api/v1/articles/{$article->id}/boost", ['duree_heures' => 24])->assertCreated();

        $this->assertSame(['boost.active'], $this->types($seller));
    }

    // --- abonnements ---------------------------------------------------------------------------

    public function test_subscribing_cancelling_and_expiring_notify_the_reseller(): void
    {
        $pro = User::factory()->create(['role' => 'revendeur_pro']);
        Wallet::create(['user_id' => $pro->id, 'balance' => 100000, 'currency' => 'GNF']);
        $service = app(SubscriptionService::class);

        $service->subscribe($pro, 'pro');
        $service->cancel($pro);
        $service->subscribe($pro, 'pro');
        $pro->subscription()->update(['ends_at' => now()->subMinute()]);
        $service->expireDue();

        $this->assertSame(['abonnement.active', 'abonnement.annule', 'abonnement.active', 'abonnement.expire'], $this->types($pro));
    }

    // --- annonces ------------------------------------------------------------------------------

    public function test_moderation_notifies_the_owner_of_the_listing(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'vendeur']);
        $kept = $this->article($owner, ['is_verified' => false]);
        $refused = $this->article($owner, ['is_verified' => false]);
        $this->actingAs($admin);

        $this->post(route('admin.articles.verify', $kept));
        $this->post(route('admin.articles.reject', $refused), ['raison' => 'Photos floues']);

        $this->assertSame(['annonce.validee', 'annonce.rejetee'], $this->types($owner));
        $this->assertStringContainsString('Photos floues', Notification::where('type', 'annonce.rejetee')->value('message'));
    }

    // --- vérification d'identité ---------------------------------------------------------------

    public function test_identity_verification_notifies_admins_then_the_user(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $user = User::factory()->create(['role' => 'vendeur']);
        $verification = IdentityVerification::create([
            'user_id' => $user->id, 'document_type' => 'cni', 'document_path' => 'd', 'selfie_path' => 's',
            'status' => 'pending', 'submitted_at' => now(),
        ]);
        $this->actingAs($admin);

        $this->post(route('admin.verifications.approve', $verification));
        $other = IdentityVerification::create([
            'user_id' => $user->id, 'document_type' => 'cni', 'document_path' => 'd', 'selfie_path' => 's',
            'status' => 'pending', 'submitted_at' => now(),
        ]);
        $this->post(route('admin.verifications.reject', $other), ['reason' => 'Document illisible']);

        $this->assertSame(['verification.approuvee', 'verification.rejetee'], $this->types($user));
    }

    // --- signalements et demandes de rôle ------------------------------------------------------

    public function test_a_new_report_notifies_admins_and_its_resolution_notifies_owner_and_reporter(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $owner = User::factory()->create(['role' => 'vendeur']);
        $reporter = User::factory()->create(['role' => 'acheteur']);
        $article = $this->article($owner);
        Sanctum::actingAs($reporter);

        $this->postJson('/api/v1/reports', [
            'reportable_type' => 'article', 'reportable_id' => $article->id, 'reason' => 'arnaque',
        ])->assertCreated();
        $this->assertSame(['admin.signalement'], $this->types($admin));

        $this->actingAs($admin);
        $this->post(route('admin.reports.resolve', Report::first()), ['action' => 'remove_content', 'note' => 'Arnaque confirmée']);

        $this->assertSame(['annonce.retiree'], $this->types($owner));
        $this->assertSame(['signalement.traite'], $this->types($reporter));
    }

    public function test_a_complete_role_request_notifies_admins_but_the_registration_stub_does_not(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $this->postJson('/api/v1/auth/register', [
            'name' => 'Awa', 'phone' => '622000001', 'password' => 'secret-123', 'password_confirmation' => 'secret-123', 'role' => 'motard',
        ])->assertCreated();
        $this->assertSame([], $this->types($admin));

        $user = User::factory()->create(['role' => 'acheteur']);
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/role-request', [
            'role' => 'revendeur_pro', 'details' => ['business_name' => 'Chez Awa', 'address' => 'Madina marché'],
        ])->assertCreated();

        $this->assertSame(['admin.role_demande'], $this->types($admin));
    }

    // --- ouverture depuis la notification push -------------------------------------------------

    public function test_every_new_type_opens_an_app_screen(): void
    {
        $screens = [
            'paiement.confirme' => '/orders/', 'paiement.recu' => '/orders/', 'paiement.echoue' => '/orders/',
            'recharge.recue' => '/wallet/', 'boost.active' => '/profile/listings/', 'annonce.validee' => '/profile/listings/',
            'annonce.rejetee' => '/profile/listings/', 'annonce.vendue' => '/profile/listings/', 'annonce.retiree' => '/profile/listings/',
            'abonnement.active' => '/subscription/', 'abonnement.annule' => '/subscription/', 'abonnement.expire' => '/subscription/',
            'verification.approuvee' => '/verification/', 'verification.rejetee' => '/verification/',
            'compte.suspendu' => '/notifications/', 'compte.avertissement' => '/notifications/', 'signalement.traite' => '/notifications/',
        ];

        foreach ($screens as $type => $screen) {
            // Même avec un article dans les données, l'écran du type l'emporte.
            $this->assertSame($screen, PushRoute::forNotification($type, ['article_id' => 1]), $type);
        }
    }
}
