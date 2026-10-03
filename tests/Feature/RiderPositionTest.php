<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\User;
use App\Services\Delivery\TrackingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RiderPositionTest extends TestCase
{
    use RefreshDatabase;

    private function delivery(?User $rider, string $status): Delivery
    {
        $seller = User::factory()->create(['role' => 'vendeur']);
        $buyer = User::factory()->create(['role' => 'acheteur']);
        $category = Category::firstOrCreate(['slug' => 'test'], ['libelle' => 'Test', 'icon' => 'bx-box']);
        $article = Article::create([
            'user_id' => $seller->id, 'category_id' => $category->id, 'titre' => 'A', 'slug' => 'a-' . uniqid(),
            'description' => 'd', 'prix' => 1000, 'currency' => 'GNF', 'localisation' => 'Conakry',
            'with_delivery' => true, 'delivery_prix' => 500, 'is_published' => true,
        ]);
        $order = Order::create([
            'reference' => 'CMD-' . strtoupper(substr(uniqid(), -6)), 'buyer_id' => $buyer->id, 'seller_id' => $seller->id,
            'article_id' => $article->id, 'prix_article' => 1000, 'with_delivery' => true, 'delivery_prix' => 500,
            'total' => 1500, 'status' => 'paye',
        ]);

        return Delivery::create([
            'order_id' => $order->id, 'rider_id' => $rider?->id, 'pickup_adresse' => 'a', 'pickup_latitude' => 0, 'pickup_longitude' => 0,
            'delivery_adresse' => 'b', 'delivery_latitude' => 0, 'delivery_longitude' => 0, 'prix' => 500, 'status' => $status,
        ]);
    }

    private function position(array $over = [])
    {
        return $this->postJson('/api/v1/deliveries/position', array_merge(['latitude' => 9.5092, 'longitude' => -13.7122], $over));
    }

    public function test_one_position_updates_every_active_delivery_of_the_rider(): void
    {
        $rider = User::factory()->create(['role' => 'motard']);
        $accepted = $this->delivery($rider, 'acceptee');
        $onTheRoad = $this->delivery($rider, 'en_cours');
        $delivered = $this->delivery($rider, 'livree');
        $other = $this->delivery(User::factory()->create(['role' => 'motard']), 'en_cours');
        Sanctum::actingAs($rider);

        $this->position()->assertOk()->assertJsonPath('updated', 2);

        $this->assertCount(1, $accepted->fresh()->tracking_json);
        $this->assertCount(1, $onTheRoad->fresh()->tracking_json);
        $this->assertNull($delivered->fresh()->tracking_json);
        $this->assertNull($other->fresh()->tracking_json);
        $this->assertEquals(9.5092, $accepted->fresh()->tracking_json[0]['lat']);
    }

    public function test_a_position_without_an_active_ride_changes_nothing(): void
    {
        $rider = User::factory()->create(['role' => 'motard']);
        $done = $this->delivery($rider, 'effectuee');
        Sanctum::actingAs($rider);

        $this->position()->assertOk()->assertJsonPath('updated', 0);
        $this->assertNull($done->fresh()->tracking_json);
    }

    public function test_only_riders_send_positions_and_coordinates_are_checked(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'acheteur']));
        $this->position()->assertForbidden();

        Sanctum::actingAs(User::factory()->create(['role' => 'motard']));
        $this->position(['latitude' => 120])->assertUnprocessable()->assertJsonValidationErrors('latitude');
        $this->position(['longitude' => 'abc'])->assertUnprocessable()->assertJsonValidationErrors('longitude');
    }

    public function test_the_track_keeps_only_the_most_recent_points(): void
    {
        $delivery = $this->delivery(User::factory()->create(['role' => 'motard']), 'en_cours');
        $service = app(TrackingService::class);

        for ($i = 0; $i < TrackingService::MAX_POINTS + 25; $i++) {
            $service->updatePosition($delivery->fresh(), 9.0 + $i / 100000, -13.0);
        }

        $track = $delivery->fresh()->tracking_json;
        $this->assertCount(TrackingService::MAX_POINTS, $track);
        $this->assertEqualsWithDelta(9.0 + (TrackingService::MAX_POINTS + 24) / 100000, end($track)['lat'], 0.0000001);
    }

    public function test_a_rider_gets_a_position_only_token_and_old_ones_are_revoked(): void
    {
        $rider = User::factory()->create(['role' => 'motard']);
        Sanctum::actingAs($rider);

        $first = $this->postJson('/api/v1/deliveries/position-token')->assertCreated()->json('token');
        $second = $this->postJson('/api/v1/deliveries/position-token')->assertCreated()->json('token');

        $this->assertNotSame($first, $second);
        $this->assertSame(1, $rider->tokens()->where('name', 'rider-position')->count());

        $this->deleteJson('/api/v1/deliveries/position-token')->assertNoContent();
        $this->assertSame(0, $rider->tokens()->where('name', 'rider-position')->count());
    }

    public function test_only_riders_can_get_a_position_token(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'acheteur']));

        $this->postJson('/api/v1/deliveries/position-token')->assertForbidden();
    }

    public function test_the_position_token_can_send_positions_but_nothing_else(): void
    {
        $rider = User::factory()->create(['role' => 'motard']);
        $delivery = $this->delivery($rider, 'en_cours');
        $token = $rider->createToken('rider-position', ['position'])->plainTextToken;

        $this->withToken($token)->postJson('/api/v1/deliveries/position', ['latitude' => 9.5, 'longitude' => -13.7])
            ->assertOk()->assertJsonPath('updated', 1);
        $this->assertCount(1, $delivery->fresh()->tracking_json);

        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/profile')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->getJson('/api/v1/wallet')->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->postJson('/api/v1/wallet/withdrawals', ['montant' => 20000, 'phone' => '622000000', 'operator' => 'orange_money'])->assertForbidden();
        $this->app['auth']->forgetGuards();
        $this->withToken($token)->deleteJson('/api/v1/profile', [])->assertForbidden();
    }

    public function test_a_normal_login_token_keeps_full_access(): void
    {
        $rider = User::factory()->create(['role' => 'motard']);
        $token = $rider->createToken('auth-token')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/profile')->assertOk();
    }
}
