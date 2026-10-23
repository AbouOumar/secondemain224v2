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

    public function test_forgot_password_requires_an_email(): void
    {
        $this->postJson('/api/v1/auth/forgot-password', ['phone' => '622000001'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_api_password_reset_works_with_the_emailed_token(): void
    {
        $user = User::factory()->create();
        $token = \Illuminate\Support\Facades\Password::createToken($user);

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => 'nouveau-mdp-123',
            'password_confirmation' => 'nouveau-mdp-123',
        ])->assertOk();

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('nouveau-mdp-123', $user->fresh()->password));

        $this->postJson('/api/v1/auth/reset-password', [
            'email' => $user->email,
            'token' => 'mauvais',
            'password' => 'autre-mdp-1234',
            'password_confirmation' => 'autre-mdp-1234',
        ])->assertStatus(400);
    }
}
