<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthHardeningTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_rate_limited_with_a_french_message(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/v1/auth/login', ['login' => 'inconnu@example.com', 'password' => 'mauvais-mdp'])->assertStatus(401);
        }

        $this->postJson('/api/v1/auth/login', ['login' => 'inconnu@example.com', 'password' => 'mauvais-mdp'])
            ->assertStatus(429)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Trop de tentatives'));
    }

    public function test_registration_and_password_reset_are_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/forgot-password', ['email' => "x{$i}@example.com"])->assertOk();
        }
        $this->postJson('/api/v1/auth/forgot-password', ['email' => 'x6@example.com'])->assertStatus(429);

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/auth/register', [])->assertUnprocessable();
        }
        $this->postJson('/api/v1/auth/register', [])->assertStatus(429);
    }

    public function test_a_suspended_account_cannot_log_in(): void
    {
        User::factory()->create(['email' => 'sus@example.com', 'password' => Hash::make('secret-123'), 'status' => 'suspendu']);

        $this->postJson('/api/v1/auth/login', ['login' => 'sus@example.com', 'password' => 'secret-123'])
            ->assertStatus(403)
            ->assertJsonMissingPath('token');
    }

    public function test_a_suspended_account_loses_api_access(): void
    {
        $user = User::factory()->create(['status' => 'actif']);
        $token = $user->createToken('auth-token')->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/profile')->assertOk();

        $user->update(['status' => 'suspendu']);
        $this->app['auth']->forgetGuards(); // chaque vraie requête repart d'un garde vide

        $this->withToken($token)->getJson('/api/v1/profile')->assertStatus(403);
    }

    public function test_forgot_password_by_phone_does_not_reveal_whether_the_phone_exists(): void
    {
        User::factory()->create(['phone' => '622000001']);

        $known = $this->postJson('/api/v1/auth/forgot-password', ['phone' => '622000001']);
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['phone' => '699999999']);

        $known->assertOk();
        $unknown->assertOk();
        $this->assertSame($known->json('message'), $unknown->json('message'));
    }
}
