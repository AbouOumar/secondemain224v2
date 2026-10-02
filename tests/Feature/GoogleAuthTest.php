<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleAuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.google.client_id' => 'client-test.apps.googleusercontent.com']);
    }

    private function fakeTokenInfo(array $overrides = [], int $status = 200): void
    {
        Http::fake([
            'oauth2.googleapis.com/tokeninfo*' => Http::response(array_merge([
                'iss' => 'https://accounts.google.com',
                'aud' => 'client-test.apps.googleusercontent.com',
                'sub' => '1234567890',
                'email' => 'awa@gmail.com',
                'email_verified' => 'true',
                'name' => 'Awa Diallo',
                'exp' => (string) (time() + 3600),
            ], $overrides), $status),
        ]);
    }

    public function test_valid_google_token_creates_account_and_returns_api_token(): void
    {
        $this->fakeTokenInfo();

        $this->postJson('/api/v1/auth/oauth/google', ['token' => 'id-token'])
            ->assertOk()
            ->assertJsonPath('user.email', 'awa@gmail.com')
            ->assertJsonStructure(['token']);

        $user = User::where('email', 'awa@gmail.com')->firstOrFail();
        $this->assertNotNull($user->email_verified_at);
        $this->assertDatabaseHas('oauth_providers', ['user_id' => $user->id, 'provider' => 'google', 'provider_id' => '1234567890']);
        $this->assertDatabaseHas('wallets', ['user_id' => $user->id]);
    }

    public function test_google_login_links_existing_account_by_verified_email(): void
    {
        $existing = User::factory()->create(['email' => 'awa@gmail.com']);
        $this->fakeTokenInfo();

        $this->postJson('/api/v1/auth/oauth/google', ['token' => 'id-token'])
            ->assertOk()
            ->assertJsonPath('user.id', $existing->id);

        $this->assertDatabaseCount('users', 1);
    }

    public function test_token_rejected_by_google_is_refused(): void
    {
        $this->fakeTokenInfo([], 400);

        $this->postJson('/api/v1/auth/oauth/google', ['token' => 'faux'])->assertUnauthorized();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_token_issued_for_another_app_is_refused(): void
    {
        $this->fakeTokenInfo(['aud' => 'autre-app.apps.googleusercontent.com']);

        $this->postJson('/api/v1/auth/oauth/google', ['token' => 'id-token'])->assertUnauthorized();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_unverified_google_email_cannot_take_over_existing_account(): void
    {
        User::factory()->create(['email' => 'awa@gmail.com']);
        $this->fakeTokenInfo(['email_verified' => 'false']);

        $this->postJson('/api/v1/auth/oauth/google', ['token' => 'id-token'])->assertUnprocessable();

        $this->assertDatabaseCount('oauth_providers', 0);
    }

    public function test_facebook_login_is_disabled(): void
    {
        $this->postJson('/api/v1/auth/oauth/facebook', ['token' => 'nimporte'])->assertStatus(501);

        $this->assertDatabaseCount('users', 0);
    }

    public function test_web_callback_logs_user_in_with_verified_google_profile(): void
    {
        config(['services.google.client_secret' => 'secret']);
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'access']),
            'www.googleapis.com/oauth2/v3/userinfo' => Http::response([
                'sub' => '1234567890',
                'email' => 'awa@gmail.com',
                'email_verified' => true,
                'name' => 'Awa Diallo',
            ]),
        ]);

        $this->withSession(['google_oauth_state' => 'etat'])
            ->get('/auth/google/callback?state=etat&code=code')
            ->assertRedirect(route('profile.edit')); // nouveau compte : numéro à compléter

        $this->assertAuthenticatedAs(User::where('email', 'awa@gmail.com')->firstOrFail());
    }
}
