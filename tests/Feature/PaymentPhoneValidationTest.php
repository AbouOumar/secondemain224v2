<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentPhoneValidationTest extends TestCase
{
    use RefreshDatabase;

    private function commande(User $buyer): Order
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $category = Category::create(['libelle' => 'Test', 'slug' => 'test', 'icon' => 'bx-box']);
        $article = Article::create([
            'user_id' => $seller->id, 'category_id' => $category->id, 'titre' => 'Article test',
            'slug' => 'article-test', 'description' => 'Description', 'prix' => 1000,
            'currency' => 'GNF', 'localisation' => 'Conakry',
        ]);

        return Order::create([
            'reference' => 'CMD-TEST', 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'article_id' => $article->id, 'prix_article' => 1000, 'total' => 1000,
            'status' => 'en_attente_paiement',
        ]);
    }

    public function test_refuse_un_telephone_avec_des_lettres(): void
    {
        $user = User::factory()->create();
        $order = $this->commande($user);

        foreach (['abc123456', '62a230001', '12345', '622-30-00-01'] as $phone) {
            $this->actingAs($user)->post(route('payment.process.djomy', $order), ['phone' => $phone])
                ->assertSessionHasErrors('phone');
        }
    }

    public function test_accepte_un_telephone_valide(): void
    {
        $user = User::factory()->create();
        $order = $this->commande($user);

        foreach (['622 30 00 01', '+224622300001'] as $phone) {
            $this->actingAs($user)->post(route('payment.process.djomy', $order), ['phone' => $phone])
                ->assertSessionDoesntHaveErrors('phone');
        }
    }
}
