<?php

namespace Tests\Feature;

use App\Models\IdentityVerification;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private function pro(int $balance = 0): User
    {
        $user = User::factory()->create(['role' => 'revendeur_pro', 'is_verified' => false]);
        Wallet::create(['user_id' => $user->id, 'balance' => $balance, 'currency' => 'GNF']);

        return $user;
    }

    private function subscribe(string $plan)
    {
        return $this->postJson('/api/v1/subscription/store', ['plan_type' => $plan, 'payment_method' => 'wallet']);
    }

    public function test_only_professional_resellers_can_subscribe(): void
    {
        $this->postJson('/api/v1/subscription/store', ['plan_type' => 'pro'])->assertUnauthorized();

        $user = User::factory()->create(['role' => 'vendeur', 'is_verified' => false]);
        Wallet::create(['user_id' => $user->id, 'balance' => 100000, 'currency' => 'GNF']);
        Sanctum::actingAs($user);

        $this->subscribe('pro')->assertForbidden();
        $this->assertSame(0, Subscription::count());
        $this->assertFalse((bool) $user->fresh()->is_verified);
    }

    public function test_the_plans_and_prices_are_listed(): void
    {
        Sanctum::actingAs($this->pro());

        $plans = collect($this->getJson('/api/v1/subscription/plans')->assertOk()->json('data'))->keyBy('key');

        $this->assertSame(0, $plans['basic']['price']);
        $this->assertSame(5000, $plans['pro']['price']);
        $this->assertSame(15000, $plans['enterprise']['price']);
        $this->assertNotEmpty($plans['pro']['features']);
    }

    public function test_a_paid_plan_is_charged_to_the_wallet_for_one_month_and_gives_the_badge(): void
    {
        $user = $this->pro(10000);
        Sanctum::actingAs($user);

        $this->subscribe('pro')->assertCreated()->assertJsonPath('subscription.plan_type', 'pro');

        $subscription = Subscription::where('user_id', $user->id)->firstOrFail();
        $this->assertSame('active', $subscription->status);
        $this->assertEqualsWithDelta(30, now()->diffInDays($subscription->ends_at, true), 1);
        $this->assertEquals(5000, $user->wallet()->first()->balance);
        $this->assertSame(1, Transaction::where('source', 'abonnement')->where('type', 'debit')->count());
        $this->assertTrue((bool) $user->fresh()->is_verified);
    }

    public function test_an_empty_wallet_gets_nothing(): void
    {
        $user = $this->pro(1000);
        Sanctum::actingAs($user);

        $this->subscribe('pro')->assertStatus(422);

        $this->assertSame(0, Subscription::count());
        $this->assertEquals(1000, $user->wallet()->first()->balance);
        $this->assertFalse((bool) $user->fresh()->is_verified);
    }

    public function test_subscribing_twice_to_the_same_plan_does_not_charge_twice(): void
    {
        $user = $this->pro(20000);
        Sanctum::actingAs($user);

        $this->subscribe('pro')->assertCreated();
        $this->subscribe('pro')->assertStatus(409);

        $this->assertEquals(15000, $user->wallet()->first()->balance);
        $this->assertSame(1, Transaction::where('source', 'abonnement')->count());
    }

    public function test_the_free_plan_costs_nothing_and_gives_no_badge(): void
    {
        $user = $this->pro(0);
        Sanctum::actingAs($user);

        $this->subscribe('basic')->assertCreated();

        $this->assertSame(0, Transaction::count());
        $this->assertFalse((bool) $user->fresh()->is_verified);
        $this->assertTrue(Subscription::first()->isActive());
    }

    public function test_upgrading_charges_the_new_plan(): void
    {
        $user = $this->pro(30000);
        Sanctum::actingAs($user);

        $this->subscribe('pro')->assertCreated();
        $this->subscribe('enterprise')->assertCreated();

        $this->assertEquals(10000, $user->wallet()->first()->balance); // 5 000 puis 15 000
        $this->assertSame('enterprise', Subscription::where('user_id', $user->id)->value('plan_type'));
        $this->assertSame(1, Subscription::where('user_id', $user->id)->count());
    }

    public function test_cancelling_removes_the_badge_unless_the_identity_was_verified(): void
    {
        $plain = $this->pro(10000);
        Sanctum::actingAs($plain);
        $this->subscribe('pro')->assertCreated();
        $this->postJson('/api/v1/subscription/cancel')->assertOk();
        $this->assertFalse((bool) $plain->fresh()->is_verified);
        $this->assertSame('cancelled', Subscription::where('user_id', $plain->id)->value('status'));

        $checked = $this->pro(10000);
        IdentityVerification::create([
            'user_id' => $checked->id, 'document_type' => 'id_card', 'document_path' => 'd', 'selfie_path' => 's',
            'status' => 'approved', 'submitted_at' => now(),
        ]);
        Sanctum::actingAs($checked);
        $this->subscribe('pro')->assertCreated();
        $this->postJson('/api/v1/subscription/cancel')->assertOk();
        $this->assertTrue((bool) $checked->fresh()->is_verified);
    }

    public function test_the_current_subscription_is_reported_or_null(): void
    {
        $user = $this->pro(10000);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/subscription/current')->assertOk()->assertJsonPath('subscription', null);

        $this->subscribe('pro')->assertCreated();
        $this->getJson('/api/v1/subscription/current')
            ->assertOk()
            ->assertJsonPath('subscription.plan_type', 'pro')
            ->assertJsonPath('subscription.is_active', true);
    }

    public function test_expired_subscriptions_lose_their_badge_and_active_ones_keep_it(): void
    {
        $expiring = $this->pro(10000);
        Sanctum::actingAs($expiring);
        $this->subscribe('pro')->assertCreated();
        Subscription::where('user_id', $expiring->id)->update(['ends_at' => now()->subDay()]);

        $active = $this->pro(10000);
        Sanctum::actingAs($active);
        $this->subscribe('pro')->assertCreated();

        $this->artisan('subscriptions:expire')->assertSuccessful();

        $this->assertSame('expired', Subscription::where('user_id', $expiring->id)->value('status'));
        $this->assertFalse((bool) $expiring->fresh()->is_verified);
        $this->assertSame('active', Subscription::where('user_id', $active->id)->value('status'));
        $this->assertTrue((bool) $active->fresh()->is_verified);
    }
}
