<?php

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SubscriptionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_cannot_grant_themselves_a_paid_plan_or_the_verified_badge(): void
    {
        $user = User::factory()->create(['role' => 'vendeur', 'is_verified' => false]);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/subscription/store', ['plan_type' => 'pro', 'payment_method' => 'orange_money'])
            ->assertStatus(503);

        $this->assertSame(0, Subscription::where('user_id', $user->id)->count());
        $this->assertFalse((bool) $user->fresh()->is_verified);
    }
}
