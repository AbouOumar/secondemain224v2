<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationLinkTest extends TestCase
{
    use RefreshDatabase;

    private function article(User $seller): Article
    {
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

    private function notify(User $user, string $type, array $data): Notification
    {
        return Notification::create([
            'user_id' => $user->id,
            'type' => $type,
            'title' => 'Titre',
            'message' => 'Message',
            'data' => $data,
        ]);
    }

    public function test_article_notification_links_to_the_article(): void
    {
        $user = User::factory()->create();
        $article = $this->article(User::factory()->create(['role' => 'vendeur']));

        $notif = $this->notify($user, 'alerte.recherche', ['alert_id' => 1, 'article_ids' => [$article->id]]);

        $this->assertSame(route('articles.show', $article->slug), $notif->targetUrl());
    }

    public function test_offer_notification_links_to_the_offers_page(): void
    {
        $user = User::factory()->create();
        $article = $this->article(User::factory()->create(['role' => 'vendeur']));

        $notif = $this->notify($user, 'offre.nouvelle', ['offer_id' => 1, 'article_id' => $article->id]);

        $this->assertSame(route('profile.offers.index'), $notif->targetUrl());
    }

    public function test_buyer_order_notification_links_to_payment_while_pending(): void
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $buyer = User::factory()->create();
        $article = $this->article($seller);
        $order = Order::create([
            'reference' => 'CMD-ABC123',
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'article_id' => $article->id,
            'prix_article' => 1000000,
            'total' => 1000000,
            'status' => 'en_attente_paiement',
        ]);

        $forBuyer = $this->notify($buyer, 'achat.valide', ['order_reference' => $order->reference]);
        $forSeller = $this->notify($seller, 'nouvelle.commande', ['order_id' => $order->id]);

        $this->assertSame(route('payment.show', $order), $forBuyer->targetUrl());
        $this->assertSame(route('articles.show', $article->slug), $forSeller->targetUrl());
    }

    public function test_delivery_notification_links_to_tracking_or_rider_dashboard(): void
    {
        $buyer = User::factory()->create();
        $rider = User::factory()->create(['role' => 'motard']);

        $forBuyer = $this->notify($buyer, 'livraison.livree', ['delivery_id' => 7]);
        $forRider = $this->notify($rider, 'livraison.disponible', ['delivery_id' => 7]);

        $this->assertSame(route('deliveries.tracking', 7), $forBuyer->targetUrl());
        $this->assertSame(route('motard.dashboard'), $forRider->targetUrl());
    }

    public function test_notification_without_data_has_no_link(): void
    {
        $notif = $this->notify(User::factory()->create(), 'autre', []);

        $this->assertNull($notif->targetUrl());
    }

    public function test_notifications_page_renders_clickable_items(): void
    {
        $user = User::factory()->create();
        $article = $this->article(User::factory()->create(['role' => 'vendeur']));
        $this->notify($user, 'alerte.recherche', ['article_ids' => [$article->id]]);

        $this->actingAs($user)
            ->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('href="'.route('articles.show', $article->slug).'"', false);
    }
}
