<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Boost;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BoostSecurityTest extends TestCase
{
    use RefreshDatabase;

    private function setup_(): array
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $category = Category::create(['libelle' => 'Test', 'slug' => 'test', 'icon' => 'bx-box']);
        $article = Article::create([
            'user_id' => $seller->id, 'category_id' => $category->id, 'titre' => 'A', 'slug' => 'a-' . uniqid(),
            'description' => 'd', 'prix' => 1000, 'currency' => 'GNF', 'localisation' => 'Conakry',
            'with_delivery' => false, 'is_published' => true,
        ]);

        return [$seller, $article];
    }

    private function payment(User $owner, Article $article, string $status, int $montant): Payment
    {
        $order = Order::create([
            'reference' => 'CMD-' . strtoupper(substr(uniqid(), -6)), 'buyer_id' => $owner->id, 'seller_id' => $article->user_id,
            'article_id' => $article->id, 'prix_article' => $montant, 'with_delivery' => false, 'delivery_prix' => 0,
            'total' => $montant, 'status' => 'en_attente_paiement',
        ]);

        return Payment::create([
            'reference' => 'PAY-' . strtoupper(substr(uniqid(), -8)), 'order_id' => $order->id, 'user_id' => $owner->id,
            'montant' => $montant, 'currency' => 'GNF', 'methode' => 'djomy', 'status' => $status,
        ]);
    }

    /**
     * Un Boost se paie avec le portefeuille. `payment_id` évitait le débit sans aucun contrôle ;
     * et comme un paiement est toujours rattaché à une commande, aucun ne peut légitimement
     * payer un Boost (même un paiement réel, même encaissé, même à soi).
     */
    public function test_a_payment_id_never_replaces_the_wallet(): void
    {
        [$seller, $article] = $this->setup_();
        $other = User::factory()->create();
        Sanctum::actingAs($seller);

        $payments = [
            $this->payment($other, $article, 'succes', 100000),
            $this->payment($seller, $article, 'en_attente', 100000),
            $this->payment($seller, $article, 'succes', 100000),
        ];

        foreach ($payments as $payment) {
            $this->postJson("/api/v1/articles/{$article->id}/boost", ['duree_heures' => 24, 'payment_id' => $payment->id])
                ->assertStatus(422)
                ->assertJsonValidationErrors('payment_id');
        }
        $this->assertSame(0, Boost::count());
        $this->assertFalse((bool) $article->fresh()->is_boosted);
    }

    public function test_without_payment_the_wallet_is_charged_and_an_empty_wallet_is_refused(): void
    {
        [$seller, $article] = $this->setup_();
        Sanctum::actingAs($seller);

        $this->postJson("/api/v1/articles/{$article->id}/boost", ['duree_heures' => 24])->assertStatus(400);
        $this->assertSame(0, Boost::count());
    }
}
