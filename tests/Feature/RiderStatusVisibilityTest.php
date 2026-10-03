<?php

namespace Tests\Feature;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RiderStatusVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_rider_sees_their_own_status(): void
    {
        $rider = User::factory()->create(['role' => 'motard', 'rider_status' => 'en_ligne']);

        Sanctum::actingAs($rider);
        $this->getJson('/api/v1/profile')->assertOk()->assertJsonPath('data.rider_status', 'en_ligne');
    }

    public function test_other_users_do_not_see_a_riders_status(): void
    {
        $rider = User::factory()->create(['role' => 'motard', 'rider_status' => 'en_ligne']);
        $other = User::factory()->create(['role' => 'acheteur']);

        $request = Request::create('/');
        $request->setUserResolver(fn () => $other);

        $this->assertArrayNotHasKey('rider_status', (new UserResource($rider))->resolve($request));
    }
}
