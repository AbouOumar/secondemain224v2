<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\Offer\OfferService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OrderDeliveryChoiceTest extends TestCase
{
    use RefreshDatabase;

    /** Commande née d'une offre acceptée à 60 000 GNF, sur une annonce à 100 000 qui propose la livraison à 5 000. */
    private function orderFromOffer(bool $articleDelivers = true): Order
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $buyer = User::factory()->create(['role' => 'acheteur']);
        $category = Category::firstOrCreate(['slug' => 'test'], ['libelle' => 'Test', 'icon' => 'bx-box']);
        $article = Article::create([
            'user_id' => $seller->id, 'category_id' => $category->id, 'titre' => 'Velo', 'slug' => 'velo-' . uniqid(),
            'description' => 'd', 'prix' => 100000, 'currency' => 'GNF', 'localisation' => 'Conakry',
            'with_delivery' => $articleDelivers, 'delivery_prix' => $articleDelivers ? 5000 : null, 'is_published' => true,
        ]);
        $offers = app(OfferService::class);
        $offer = $offers->make($buyer, $article, 60000);

        return $offers->accept($offer, $seller);
    }

    private function address(Order $order, string $text = 'Kaloum, près du marché')
    {
        return $this->postJson("/api/v1/orders/{$order->reference}/delivery", ['delivery_adresse' => $text]);
    }

    public function test_the_buyer_adds_delivery_to_an_order_born_from_an_offer(): void
    {
        $order = $this->orderFromOffer();
        $this->assertFalse((bool) $order->with_delivery);
        Sanctum::actingAs($order->buyer);

        $this->address($order)
            ->assertOk()
            ->assertJsonPath('data.with_delivery', true)
            ->assertJsonPath('data.total', 65000)          // 60 000 négociés + 5 000 de livraison
            ->assertJsonPath('data.delivery.delivery_adresse', 'Kaloum, près du marché');

        $fresh = $order->fresh();
        $this->assertSame('en_attente_paiement', $fresh->status->value);
        $this->assertEquals(60000, $fresh->prix_article);
        $this->assertEquals(5000, $fresh->delivery_prix);
        $delivery = Delivery::where('order_id', $order->id)->firstOrFail();
        $this->assertSame('en_attente', $delivery->status->value);
        $this->assertEquals(5000, $delivery->prix);
        $this->assertSame('Conakry', $delivery->pickup_adresse);
    }

    public function test_changing_the_address_does_not_duplicate_the_delivery(): void
    {
        $order = $this->orderFromOffer();
        Sanctum::actingAs($order->buyer);

        $this->address($order, 'Ancienne adresse')->assertOk();
        $this->address($order, 'Nouvelle adresse')->assertOk()->assertJsonPath('data.total', 65000);

        $this->assertSame(1, Delivery::where('order_id', $order->id)->count());
        $this->assertSame('Nouvelle adresse', Delivery::where('order_id', $order->id)->value('delivery_adresse'));
    }

    public function test_the_buyer_can_go_back_to_hand_over(): void
    {
        $order = $this->orderFromOffer();
        Sanctum::actingAs($order->buyer);
        $this->address($order)->assertOk();

        $this->deleteJson("/api/v1/orders/{$order->reference}/delivery")
            ->assertOk()
            ->assertJsonPath('data.with_delivery', false)
            ->assertJsonPath('data.total', 60000);

        $this->assertSame(0, Delivery::where('order_id', $order->id)->count());
        $this->assertEquals(0, $order->fresh()->delivery_prix);
    }

    public function test_an_address_is_required(): void
    {
        $order = $this->orderFromOffer();
        Sanctum::actingAs($order->buyer);

        $this->postJson("/api/v1/orders/{$order->reference}/delivery", [])->assertUnprocessable()->assertJsonValidationErrors('delivery_adresse');
        $this->assertSame(0, Delivery::count());
    }

    public function test_an_article_that_does_not_deliver_cannot_get_delivery(): void
    {
        $order = $this->orderFromOffer(false);
        Sanctum::actingAs($order->buyer);

        $this->address($order)->assertStatus(422);
        $this->assertFalse((bool) $order->fresh()->with_delivery);
    }

    public function test_only_the_buyer_decides(): void
    {
        $order = $this->orderFromOffer();

        Sanctum::actingAs($order->seller);
        $this->address($order)->assertForbidden();

        Sanctum::actingAs(User::factory()->create());
        $this->address($order)->assertForbidden();

        $this->assertSame(0, Delivery::count());
    }

    public function test_the_choice_is_locked_once_a_payment_has_started(): void
    {
        $order = $this->orderFromOffer();
        Sanctum::actingAs($order->buyer);
        Payment::create([
            'reference' => 'PAY-LOCK', 'order_id' => $order->id, 'user_id' => $order->buyer_id, 'montant' => 60000,
            'currency' => 'GNF', 'methode' => 'djomy', 'status' => 'en_attente',
        ]);

        $this->address($order)->assertStatus(409);

        $this->assertEquals(60000, $order->fresh()->total);
    }

    public function test_the_choice_is_locked_once_the_order_is_paid(): void
    {
        $order = $this->orderFromOffer();
        $order->update(['status' => 'paye']);
        Sanctum::actingAs($order->buyer);

        $this->address($order)->assertForbidden();
        $this->deleteJson("/api/v1/orders/{$order->reference}/delivery")->assertForbidden();
    }

    public function test_a_failed_payment_does_not_lock_the_choice(): void
    {
        $order = $this->orderFromOffer();
        Sanctum::actingAs($order->buyer);
        Payment::create([
            'reference' => 'PAY-FAIL', 'order_id' => $order->id, 'user_id' => $order->buyer_id, 'montant' => 60000,
            'currency' => 'GNF', 'methode' => 'djomy', 'status' => 'echoue',
        ]);

        $this->address($order)->assertOk()->assertJsonPath('data.total', 65000);
    }
}
