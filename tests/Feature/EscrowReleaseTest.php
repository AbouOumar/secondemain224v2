<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use App\Services\Escrow\EscrowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EscrowReleaseTest extends TestCase
{
    use RefreshDatabase;

    private function makeOrder(int $prix = 100000, bool $withDelivery = false, int $deliveryPrix = 0): Order
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
            'with_delivery' => $withDelivery,
            'delivery_prix' => $deliveryPrix,
        ]);

        return Order::create([
            'reference' => 'CMD-' . strtoupper(uniqid()),
            'buyer_id' => $buyer->id,
            'seller_id' => $seller->id,
            'article_id' => $article->id,
            'prix_article' => $prix,
            'with_delivery' => $withDelivery,
            'delivery_prix' => $deliveryPrix,
            'total' => $prix + $deliveryPrix,
            'status' => 'paye',
        ]);
    }

    public function test_hold_creates_escrow_with_no_commission_by_default(): void
    {
        config(['marketplace.commission_rate' => 0.0]);
        $order = $this->makeOrder(prix: 100000);

        $escrow = app(EscrowService::class)->hold($order);

        $this->assertSame('retenu', $escrow->status->value);
        $this->assertSame(100000, $escrow->amount);
        $this->assertSame(0, $escrow->commission_amount);
        $this->assertSame(100000, $escrow->seller_amount);
    }

    public function test_hold_applies_configured_commission_rate(): void
    {
        config(['marketplace.commission_rate' => 0.05]);
        $order = $this->makeOrder(prix: 100000);

        $escrow = app(EscrowService::class)->hold($order);

        $this->assertSame(5000, $escrow->commission_amount);
        $this->assertSame(95000, $escrow->seller_amount);
    }

    public function test_hold_is_idempotent(): void
    {
        $order = $this->makeOrder();
        $service = app(EscrowService::class);

        $first = $service->hold($order);
        $second = $service->hold($order);

        $this->assertSame($first->id, $second->id);
        $this->assertSame(1, \App\Models\Escrow::where('order_id', $order->id)->count());
    }

    public function test_release_credits_seller_wallet_and_marks_escrow_libere(): void
    {
        $order = $this->makeOrder(prix: 100000);
        $service = app(EscrowService::class);
        $escrow = $service->hold($order);

        $service->release($escrow, 'confirmation_reception');

        $escrow->refresh();
        $this->assertSame('libere', $escrow->status->value);
        $this->assertNotNull($escrow->released_at);

        $wallet = $order->seller->wallet;
        $this->assertNotNull($wallet);
        $this->assertEquals(100000, $wallet->balance);
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'credit',
            'montant' => 100000,
            'source' => 'vente',
            'source_id' => $order->id,
        ]);
    }

    public function test_release_is_idempotent_and_does_not_double_credit(): void
    {
        $order = $this->makeOrder(prix: 100000);
        $service = app(EscrowService::class);
        $escrow = $service->hold($order);

        $service->release($escrow, 'confirmation_reception');
        $service->release($escrow, 'confirmation_reception');

        $wallet = $order->seller->wallet;
        $this->assertEquals(100000, $wallet->balance);
        $this->assertSame(1, \App\Models\Transaction::where('wallet_id', $wallet->id)->count());
    }

    public function test_refund_credits_buyer_wallet_and_marks_escrow_rembourse(): void
    {
        $order = $this->makeOrder(prix: 100000);
        $service = app(EscrowService::class);
        $escrow = $service->hold($order);

        $service->refund($escrow, 'annulation_commande');

        $escrow->refresh();
        $this->assertSame('rembourse', $escrow->status->value);

        $wallet = $order->buyer->wallet;
        $this->assertEquals(100000, $wallet->balance);
        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $wallet->id,
            'source' => 'remboursement',
            'source_id' => $order->id,
        ]);
    }

    public function test_release_after_refund_is_a_no_op(): void
    {
        $order = $this->makeOrder(prix: 100000);
        $service = app(EscrowService::class);
        $escrow = $service->hold($order);

        $service->refund($escrow, 'annulation_commande');
        $service->release($escrow, 'confirmation_reception');

        $escrow->refresh();
        $this->assertSame('rembourse', $escrow->status->value);
        $this->assertNull($order->seller->fresh()->wallet);
    }
}
