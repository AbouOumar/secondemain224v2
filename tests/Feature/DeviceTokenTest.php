<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DeviceTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_logged_in_user_registers_a_device_once(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->postJson('/api/v1/devices', ['token' => 'tok-1', 'platform' => 'android'])->assertNoContent();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/devices', ['token' => 'tok-1', 'platform' => 'android'])->assertNoContent();

        $this->assertDatabaseCount('device_tokens', 1);
        $this->assertDatabaseHas('device_tokens', ['user_id' => $user->id, 'token' => 'tok-1', 'platform' => 'android']);
    }

    public function test_shared_phone_token_moves_to_the_new_user(): void
    {
        [$a, $b] = User::factory()->count(2)->create();

        $this->actingAs($a, 'sanctum')->postJson('/api/v1/devices', ['token' => 'tok-1', 'platform' => 'android']);
        $this->actingAs($b, 'sanctum')->postJson('/api/v1/devices', ['token' => 'tok-1', 'platform' => 'android']);

        $this->assertSame(0, $a->deviceTokens()->count());
        $this->assertSame(1, $b->deviceTokens()->count());
    }

    public function test_user_removes_only_his_own_device(): void
    {
        [$a, $b] = User::factory()->count(2)->create();
        $a->deviceTokens()->create(['token' => 'tok-a', 'platform' => 'android']);
        $b->deviceTokens()->create(['token' => 'tok-b', 'platform' => 'android']);

        $this->actingAs($a, 'sanctum')->deleteJson('/api/v1/devices', ['token' => 'tok-b'])->assertNoContent();
        $this->assertDatabaseHas('device_tokens', ['token' => 'tok-b']);

        $this->actingAs($a, 'sanctum')->deleteJson('/api/v1/devices', ['token' => 'tok-a'])->assertNoContent();
        $this->assertDatabaseMissing('device_tokens', ['token' => 'tok-a']);
    }

    public function test_validation_and_authentication(): void
    {
        $this->postJson('/api/v1/devices', ['token' => 'x', 'platform' => 'android'])->assertUnauthorized();

        $user = User::factory()->create();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/devices', ['token' => '', 'platform' => 'android'])->assertUnprocessable();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/devices', ['token' => 'x', 'platform' => 'windows'])->assertUnprocessable();
    }
}
