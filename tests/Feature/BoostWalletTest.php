<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class BoostWalletTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_boost_is_paid_from_the_wallet_and_recorded(): void
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        Wallet::create(['user_id' => $seller->id, 'balance' => 20000, 'currency' => 'GNF']);
        $category = Category::create(['libelle' => 'Test', 'slug' => 'test', 'icon' => 'bx-box']);
        $article = Article::create([
            'user_id' => $seller->id, 'category_id' => $category->id, 'titre' => 'A', 'slug' => 'a-' . uniqid(),
            'description' => 'd', 'prix' => 1000, 'currency' => 'GNF', 'localisation' => 'Conakry',
            'with_delivery' => false, 'is_published' => true,
        ]);
        Sanctum::actingAs($seller);

        $this->postJson("/api/v1/articles/{$article->id}/boost", ['duree_heures' => 24])->assertCreated();

        $this->assertEquals(8000, $seller->wallet()->first()->balance); // 24 h x 500 GNF
        $this->assertSame(1, Transaction::where('source', 'boost')->where('type', 'debit')->count());
        $this->assertTrue((bool) $article->fresh()->is_boosted);
    }
}
