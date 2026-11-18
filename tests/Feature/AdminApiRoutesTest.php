<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminApiRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_api_lives_under_api_v1_admin(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'actif']);

        $this->actingAs($admin, 'sanctum')->getJson('/api/v1/admin/dashboard')->assertOk();
        $this->actingAs($admin, 'sanctum')->getJson('/v1/admin/dashboard')->assertNotFound();
    }

    public function test_admin_api_is_reserved_to_active_admins_and_answers_in_json(): void
    {
        $this->getJson('/api/v1/admin/users')->assertUnauthorized();

        $user = User::factory()->create(['role' => 'vendeur', 'status' => 'actif']);
        $this->actingAs($user, 'sanctum')->getJson('/api/v1/admin/users')->assertForbidden();

        $suspended = User::factory()->create(['role' => 'admin', 'status' => 'suspendu']);
        $this->actingAs($suspended, 'sanctum')->getJson('/api/v1/admin/users')->assertForbidden();
    }
}
