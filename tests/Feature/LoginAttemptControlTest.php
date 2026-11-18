<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\Auth\AccountLockedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class LoginAttemptControlTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
        $this->user = User::factory()->create([
            'email' => 'awa@example.com',
            'phone' => '622000111',
            'password' => Hash::make('bon-mdp-123'),
            'status' => 'actif',
        ]);
    }

    private function apiLogin(string $login, string $password, string $ip = '10.0.0.1')
    {
        return $this->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson('/api/v1/auth/login', ['login' => $login, 'password' => $password]);
    }

    public function test_five_failures_lock_the_account_from_this_ip_even_with_the_right_password(): void
    {
        $this->apiLogin('awa@example.com', 'faux')->assertStatus(401)->assertJsonPath('message', 'Identifiants incorrects.');
        $this->apiLogin('awa@example.com', 'faux')->assertStatus(401);
        $this->apiLogin('awa@example.com', 'faux')->assertStatus(401)->assertJsonPath('remaining_attempts', 2)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Il vous reste 2 tentatives'));
        $this->apiLogin('awa@example.com', 'faux')->assertStatus(401)
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Dernière tentative'));
        $this->apiLogin('awa@example.com', 'faux')->assertStatus(429)
            ->assertHeader('Retry-After')
            ->assertJsonPath('message', fn ($m) => str_contains($m, 'Réessayez dans 15 minutes'));

        $this->apiLogin('awa@example.com', 'bon-mdp-123')->assertStatus(429)->assertJsonMissingPath('token');

        Notification::assertSentTo($this->user, AccountLockedNotification::class);
    }

    public function test_email_and_phone_share_the_same_counter(): void
    {
        for ($i = 0; $i < 3; $i++) {
            $this->apiLogin('awa@example.com', 'faux')->assertStatus(401);
        }
        $this->apiLogin('622000111', 'faux')->assertStatus(401);
        $this->apiLogin('622000111', 'faux')->assertStatus(429);
    }

    public function test_the_lock_expires_and_a_success_resets_the_counter(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->apiLogin('awa@example.com', 'faux');
        }
        $this->apiLogin('awa@example.com', 'bon-mdp-123')->assertStatus(429);

        $this->travel(16)->minutes();
        $this->apiLogin('awa@example.com', 'bon-mdp-123')->assertOk()->assertJsonStructure(['token']);

        // Compteur remis à zéro : 4 nouveaux échecs ne bloquent pas.
        for ($i = 0; $i < 4; $i++) {
            $this->apiLogin('awa@example.com', 'faux')->assertStatus(401);
        }
    }

    public function test_another_ip_is_not_locked_by_a_single_ip_lock(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->apiLogin('awa@example.com', 'faux', '10.0.0.1');
        }
        $this->apiLogin('awa@example.com', 'bon-mdp-123', '10.0.0.2')->assertOk();
    }

    public function test_failures_spread_over_many_ips_lock_the_whole_account(): void
    {
        for ($n = 1; $n <= 5; $n++) {
            for ($i = 0; $i < 4; $i++) {
                $this->apiLogin('awa@example.com', 'faux', "10.0.1.{$n}");
            }
        }

        $this->apiLogin('awa@example.com', 'bon-mdp-123', '10.0.9.9')
            ->assertStatus(429)
            ->assertJsonPath('message', fn ($m) => str_contains($m, '60 minutes'));
    }

    public function test_resetting_the_password_lifts_the_lock(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->apiLogin('awa@example.com', 'faux');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.1'])->postJson('/api/v1/auth/reset-password', [
            'email' => 'awa@example.com',
            'token' => Password::createToken($this->user),
            'password' => 'nouveau-mdp-123',
            'password_confirmation' => 'nouveau-mdp-123',
        ])->assertOk();

        $this->apiLogin('awa@example.com', 'nouveau-mdp-123')->assertOk();
    }

    public function test_unknown_logins_are_locked_too_without_revealing_anything(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->apiLogin('personne@example.com', 'faux')->assertStatus(401);
        }
        $this->apiLogin('personne@example.com', 'faux')->assertStatus(429);
        Notification::assertNothingSent();
    }

    public function test_web_login_is_locked_after_five_failures(): void
    {
        for ($i = 0; $i < 4; $i++) {
            $this->from('/login')->post('/login', ['login' => 'awa@example.com', 'password' => 'faux'])
                ->assertRedirect('/login')->assertSessionHasErrors('login');
        }
        $this->from('/login')->post('/login', ['login' => 'awa@example.com', 'password' => 'faux'])
            ->assertSessionHasErrors(['login' => 'Trop de tentatives de connexion. Réessayez dans 15 minutes.']);

        $this->from('/login')->post('/login', ['login' => 'awa@example.com', 'password' => 'bon-mdp-123'])
            ->assertSessionHasErrors('login');
        $this->assertGuest();
    }

    public function test_web_login_still_works_normally(): void
    {
        $this->post('/login', ['login' => 'awa@example.com', 'password' => 'bon-mdp-123'])->assertRedirect();
        $this->assertAuthenticatedAs($this->user);
    }
}
