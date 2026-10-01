<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\Auth\ResetPasswordNotification;
use App\Notifications\Auth\VerifyEmailNotification;
use App\Notifications\Auth\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class AuthEmailsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    private function verificationUrl(User $user, ?string $hash = null): string
    {
        return URL::temporarySignedRoute('verification.verify', now()->addHour(), [
            'id' => $user->id,
            'hash' => $hash ?? sha1($user->email),
        ]);
    }

    public function test_web_registration_sends_welcome_email_with_verification_link(): void
    {
        $this->post('/register', [
            'name' => 'Awa Diallo',
            'email' => 'awa@example.com',
            'phone' => '620000001',
            'password' => 'motdepasse',
            'password_confirmation' => 'motdepasse',
        ])->assertRedirect();

        $user = User::where('email', 'awa@example.com')->firstOrFail();

        Notification::assertSentTo($user, WelcomeNotification::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);

            return str_contains($mail->actionUrl, '/email/verify/'.$user->id.'/');
        });
    }

    public function test_api_registration_sends_welcome_email(): void
    {
        $this->postJson('/api/v1/auth/register', [
            'name' => 'Awa Diallo',
            'email' => 'awa@example.com',
            'phone' => '620000001',
            'password' => 'motdepasse',
            'password_confirmation' => 'motdepasse',
        ])->assertCreated();

        Notification::assertSentTo(User::where('email', 'awa@example.com')->firstOrFail(), WelcomeNotification::class);
    }

    public function test_welcome_email_is_skipped_for_users_without_email(): void
    {
        $user = User::factory()->create(['email' => null]);

        $this->assertSame([], (new WelcomeNotification)->via($user));
    }

    public function test_welcome_email_renders_in_french_with_branding(): void
    {
        $user = User::factory()->unverified()->create(['name' => 'Awa']);

        $html = (string) (new WelcomeNotification)->toMail($user)->render();

        $this->assertStringContainsString('Bienvenue Awa', $html);
        $this->assertStringContainsString('Confirmer mon adresse e-mail', $html);
        $this->assertStringContainsString('Tous droits réservés.', $html);
        $this->assertStringContainsString('assets/img/icon.png', $html);
    }

    public function test_new_google_user_gets_welcome_email_without_verification_step(): void
    {
        config(['services.google.client_id' => 'client-test']);
        Http::fake([
            'oauth2.googleapis.com/tokeninfo*' => Http::response([
                'iss' => 'accounts.google.com',
                'aud' => 'client-test',
                'sub' => '42',
                'email' => 'awa@gmail.com',
                'email_verified' => 'true',
                'exp' => (string) (time() + 3600),
            ]),
        ]);

        $this->postJson('/api/v1/auth/oauth/google', ['token' => 'id-token'])->assertOk();
        $this->postJson('/api/v1/auth/oauth/google', ['token' => 'id-token'])->assertOk();

        $user = User::where('email', 'awa@gmail.com')->firstOrFail();
        Notification::assertSentToTimes($user, WelcomeNotification::class, 1);
        Notification::assertSentTo($user, WelcomeNotification::class, fn ($notification) => ! str_contains($notification->toMail($user)->actionUrl, '/email/verify/'));
    }

    public function test_verification_link_confirms_email_without_being_logged_in(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get($this->verificationUrl($user))->assertRedirect(route('login'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_verification_link_with_wrong_hash_or_signature_is_rejected(): void
    {
        $user = User::factory()->unverified()->create();

        $this->get($this->verificationUrl($user, sha1('autre@example.com')))->assertRedirect(route('login'));
        $this->get('/email/verify/'.$user->id.'/'.sha1($user->email))->assertForbidden();

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
    }

    public function test_changing_email_requires_new_confirmation(): void
    {
        $user = User::factory()->create();

        $user->update(['email' => 'nouvelle@example.com']);

        $this->assertFalse($user->fresh()->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmailNotification::class);
    }

    public function test_logged_in_user_can_resend_verification_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post('/email/verification-notification')->assertRedirect();
        $this->actingAs($user, 'sanctum')->postJson('/api/v1/auth/email/verification-notification')->assertOk();

        Notification::assertSentToTimes($user, VerifyEmailNotification::class, 2);
    }

    public function test_web_forgot_password_sends_french_reset_email(): void
    {
        $user = User::factory()->create();

        $this->post('/password/forgot', ['email' => $user->email])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use ($user) {
            $mail = $notification->toMail($user);

            return $mail->subject === 'Réinitialisation de votre mot de passe'
                && str_contains($mail->actionUrl, '/password/reset/');
        });
    }

    public function test_api_forgot_password_by_email_does_not_reveal_unknown_accounts(): void
    {
        $user = User::factory()->create();

        $known = $this->postJson('/api/v1/auth/forgot-password', ['email' => $user->email])->assertOk()->json('message');
        $unknown = $this->postJson('/api/v1/auth/forgot-password', ['email' => 'inconnu@example.com'])->assertOk()->json('message');

        $this->assertSame($known, $unknown);
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }
}
