<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Delivery;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RiderDeliveryApiTest extends TestCase
{
    use RefreshDatabase;

    private function makeDelivery(): Delivery
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $buyer = User::factory()->create(['role' => 'acheteur']);
        $category = Category::create(['libelle' => 'Test ' . uniqid(), 'slug' => 'test-' . uniqid(), 'icon' => 'bx-box']);
        $article = Article::create([
            'user_id' => $seller->id,
            'category_id' => $category->id,
            'titre' => 'Colis',
            'slug' => 'colis-' . uniqid(),
            'description' => 'Description',
            'prix' => 50000,
            'currency' => 'GNF',
            'localisation' => 'Conakry',
            'with_delivery' => true,
            'delivery_prix' => 5000,
            'is_published' => true,
        ]);

        Sanctum::actingAs($buyer);
        $reference = $this->postJson('/api/v1/orders', [
            'article_id' => $article->id,
            'with_delivery' => true,
            'delivery_adresse' => 'Kaloum',
        ])->assertCreated()->json('data.reference');

        return Delivery::whereHas('order', fn ($q) => $q->where('reference', $reference))->firstOrFail();
    }

    private function rider(): User
    {
        return User::factory()->create(['role' => 'motard']);
    }

    public function test_a_rider_can_accept_then_pickup_then_complete(): void
    {
        $delivery = $this->makeDelivery();
        $rider = $this->rider();
        Sanctum::actingAs($rider);

        $this->postJson("/api/v1/deliveries/{$delivery->id}/accept")->assertOk()->assertJsonPath('data.status', 'acceptee');
        $this->assertSame($rider->id, $delivery->fresh()->rider_id);

        $this->postJson("/api/v1/deliveries/{$delivery->id}/pickup")->assertOk()->assertJsonPath('data.status', 'en_cours');
        $this->postJson("/api/v1/deliveries/{$delivery->id}/complete")->assertOk()->assertJsonPath('data.status', 'livree');
    }

    public function test_only_riders_can_accept(): void
    {
        $delivery = $this->makeDelivery();
        Sanctum::actingAs(User::factory()->create(['role' => 'acheteur']));

        $this->postJson("/api/v1/deliveries/{$delivery->id}/accept")->assertForbidden();
        $this->assertNull($delivery->fresh()->rider_id);
    }

    public function test_a_delivery_taken_by_another_rider_cannot_be_accepted(): void
    {
        $delivery = $this->makeDelivery();
        $first = $this->rider();
        Sanctum::actingAs($first);
        $this->postJson("/api/v1/deliveries/{$delivery->id}/accept")->assertOk();

        Sanctum::actingAs($this->rider());
        $this->postJson("/api/v1/deliveries/{$delivery->id}/accept")->assertForbidden();
        $this->assertSame($first->id, $delivery->fresh()->rider_id);
    }

    public function test_another_rider_cannot_pickup_or_send_positions(): void
    {
        $delivery = $this->makeDelivery();
        Sanctum::actingAs($this->rider());
        $this->postJson("/api/v1/deliveries/{$delivery->id}/accept")->assertOk();

        Sanctum::actingAs($this->rider());
        $this->postJson("/api/v1/deliveries/{$delivery->id}/pickup")->assertForbidden();
        $this->postJson("/api/v1/deliveries/{$delivery->id}/tracking", ['latitude' => 9.5, 'longitude' => -13.7])->assertForbidden();
        $this->assertNull($delivery->fresh()->tracking_json);
    }

    public function test_the_assigned_rider_can_send_positions(): void
    {
        $delivery = $this->makeDelivery();
        Sanctum::actingAs($this->rider());
        $this->postJson("/api/v1/deliveries/{$delivery->id}/accept")->assertOk();

        $this->postJson("/api/v1/deliveries/{$delivery->id}/tracking", ['latitude' => 9.5, 'longitude' => -13.7])->assertOk();
        $this->assertCount(1, $delivery->fresh()->tracking_json);
    }

    public function test_current_lists_only_my_active_deliveries(): void
    {
        $mine = $this->makeDelivery();
        $other = $this->makeDelivery();
        $rider = $this->rider();
        Sanctum::actingAs($rider);
        $this->postJson("/api/v1/deliveries/{$mine->id}/accept")->assertOk();

        $ids = collect($this->getJson('/api/v1/deliveries/current')->assertOk()->json('data'))->pluck('id');

        $this->assertSame([$mine->id], $ids->all());
        $this->assertNotContains($other->id, $ids->all());
    }

    public function test_earnings_sum_completed_deliveries(): void
    {
        $delivery = $this->makeDelivery();
        $rider = $this->rider();
        $delivery->update(['rider_id' => $rider->id, 'status' => 'effectuee', 'completed_at' => now()]);
        Sanctum::actingAs($rider);

        $this->getJson('/api/v1/deliveries/earnings')
            ->assertOk()
            ->assertJsonPath('data.aujourd_hui', 5000)
            ->assertJsonPath('data.ce_mois', 5000);
    }
}
