<?php

namespace Tests\Feature;

use App\Exceptions\OfferException;
use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use App\Services\Offer\OfferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfferNegotiationTest extends TestCase
{
    use RefreshDatabase;

    private function makeArticle(int $prix = 100000): array
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $buyer = User::factory()->create(['role' => 'acheteur']);
        $category = Category::create(['libelle' => 'Test ' . uniqid(), 'slug' => 'test-' . uniqid(), 'icon' => 'bx-box']);

        $article = Article::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'titre' => 'Article test',
            'slug' => 'article-test-' . uniqid(),
            'description' => 'Description',
            'prix' => $prix,
            'currency' => 'GNF',
            'localisation' => 'Conakry',
            'with_delivery' => false,
            'is_published' => true,
        ]);

        return [$article, $seller, $buyer];
    }

    public function test_offer_below_minimum_is_rejected(): void
    {
        [$article, , $buyer] = $this->makeArticle(prix: 100000);

        $this->expectException(OfferException::class);
        app(OfferService::class)->make($buyer, $article, 40000); // 40% < 50% minimum
    }

    public function test_seller_cannot_offer_on_own_article(): void
    {
        [$article, $seller] = $this->makeArticle(prix: 100000);

        $this->expectException(OfferException::class);
        app(OfferService::class)->make($seller, $article, 60000);
    }

    public function test_accepting_an_offer_creates_order_at_negotiated_price(): void
    {
        [$article, $seller, $buyer] = $this->makeArticle(prix: 100000);
        $service = app(OfferService::class);

        $offer = $service->make($buyer, $article, 60000);
        $order = $service->accept($offer, $seller);

        $this->assertSame(60000, (int) $order->prix_article);
        $this->assertSame($buyer->id, $order->buyer_id);
        $this->assertSame('acceptee', $offer->fresh()->status->value);
        $this->assertSame($order->id, $offer->fresh()->order_id);
    }

    public function test_counter_offer_flow(): void
    {
        [$article, $seller, $buyer] = $this->makeArticle(prix: 100000);
        $service = app(OfferService::class);

        $offer = $service->make($buyer, $article, 60000);

        // Le vendeur contre-propose
        $countered = $service->counter($offer, $seller, 80000);
        $this->assertSame('contree', $offer->fresh()->status->value);
        $this->assertSame('en_attente', $countered->status->value);
        $this->assertSame('vendeur', $countered->made_by);
        $this->assertSame($offer->id, $countered->parent_offer_id);

        // L'acheteur accepte la contre-offre du vendeur
        $order = $service->accept($countered, $buyer);
        $this->assertSame(80000, (int) $order->prix_article);
    }

    public function test_only_recipient_can_accept(): void
    {
        [$article, $seller, $buyer] = $this->makeArticle(prix: 100000);
        $service = app(OfferService::class);

        $offer = $service->make($buyer, $article, 60000);

        $this->expectException(OfferException::class);
        $service->accept($offer, $buyer); // le proposeur ne peut pas accepter sa propre offre
    }

    public function test_expire_stale_marks_old_pending_offers_expired(): void
    {
        [$article, , $buyer] = $this->makeArticle(prix: 100000);
        $service = app(OfferService::class);

        $offer = $service->make($buyer, $article, 60000);
        $offer->update(['expires_at' => now()->subHour()]);

        $count = $service->expireStale();

        $this->assertSame(1, $count);
        $this->assertSame('expiree', $offer->fresh()->status->value);
    }
}
