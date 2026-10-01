<?php

namespace Tests\Feature;

use App\Enums\EmailCategory;
use App\Mail\NewsletterMail;
use App\Models\NewsletterCampaign;
use App\Models\User;
use App\Services\Newsletter\NewsletterSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    private function subscriber(array $attributes = []): User
    {
        return User::factory()->create(['newsletter_subscribed_at' => now()] + $attributes);
    }

    private function campaign(array $attributes = []): NewsletterCampaign
    {
        return NewsletterCampaign::create($attributes + [
            'subject' => 'Les bons plans de la rentrée',
            'content' => "Découvrez nos **meilleures affaires** de la semaine.\n\n[Voir les annonces](https://natontine.com)",
        ]);
    }

    public function test_registration_subscribes_only_when_box_is_checked(): void
    {
        Notification::fake();
        $base = ['password' => 'motdepasse', 'password_confirmation' => 'motdepasse', 'name' => 'Awa'];

        $this->post('/register', $base + ['email' => 'oui@example.com', 'phone' => '620000001', 'newsletter' => '1']);
        $this->post('/register', $base + ['email' => 'non@example.com', 'phone' => '620000002']);
        $this->postJson('/api/v1/auth/register', $base + ['email' => 'api@example.com', 'phone' => '620000003', 'newsletter' => true])->assertCreated();

        $this->assertNotNull(User::where('email', 'oui@example.com')->value('newsletter_subscribed_at'));
        $this->assertNull(User::where('email', 'non@example.com')->value('newsletter_subscribed_at'));
        $this->assertNotNull(User::where('email', 'api@example.com')->value('newsletter_subscribed_at'));
    }

    public function test_only_confirmed_active_subscribers_are_recipients(): void
    {
        $ok = $this->subscriber();
        $this->subscriber(['email_verified_at' => null]);
        $this->subscriber(['status' => 'suspendu']);
        User::factory()->create();

        $this->assertEquals([$ok->id], User::newsletterRecipients()->pluck('id')->all());
    }

    public function test_campaign_is_sent_in_batches_without_duplicates(): void
    {
        Mail::fake();
        $subscribers = collect(range(1, 3))->map(fn () => $this->subscriber());
        $campaign = $this->campaign();
        $sender = app(NewsletterSender::class);

        $sender->launch($campaign);

        $this->assertSame(2, $sender->sendNextBatch(2));
        $this->assertSame(NewsletterCampaign::SENDING, $campaign->fresh()->status);

        $this->artisan('newsletter:send-batch')->assertSuccessful();
        $this->assertSame(0, $sender->sendNextBatch());

        $campaign->refresh();
        $this->assertSame(NewsletterCampaign::SENT, $campaign->status);
        $this->assertSame(3, $campaign->sent_count);
        Mail::assertSentCount(3);
        $subscribers->each(fn (User $user) => Mail::assertSent(NewsletterMail::class, fn ($mail) => $mail->hasTo($user->email)));
    }

    public function test_draft_campaigns_are_not_sent(): void
    {
        Mail::fake();
        $this->subscriber();
        $this->campaign();

        $this->assertSame(0, app(NewsletterSender::class)->sendNextBatch());
        Mail::assertNothingSent();
    }

    public function test_newsletter_email_is_personalised_with_unsubscribe_link_and_header(): void
    {
        config(['mail.default' => 'array']);
        $user = $this->subscriber(['name' => 'Awa']);
        $campaign = $this->campaign();

        app(NewsletterSender::class)->launch($campaign);
        app(NewsletterSender::class)->sendNextBatch();

        $email = app('mailer')->getSymfonyTransport()->messages()->first()->getOriginalMessage();
        $html = $email->getHtmlBody();
        $this->assertSame('Les bons plans de la rentrée', $email->getSubject());
        $this->assertStringContainsString('Bonjour Awa', $html);
        $this->assertMatchesRegularExpression('#<strong[^>]*>meilleures affaires</strong>#', $html);
        $this->assertStringContainsString('Se désinscrire', $html);
        $this->assertStringContainsString('/emails/desabonnement/'.$user->id.'/newsletter', $email->getHeaders()->get('List-Unsubscribe')->getBodyAsString());
    }

    public function test_subscriber_can_unsubscribe_from_newsletter_link(): void
    {
        $user = $this->subscriber();
        $url = URL::signedRoute('email.unsubscribe', ['user' => $user->id, 'category' => 'newsletter']);

        $this->post($url)->assertOk();

        $this->assertNull($user->fresh()->newsletter_subscribed_at);
        $this->assertFalse($user->fresh()->wantsEmailFor(EmailCategory::Newsletter));
    }

    public function test_newsletter_is_opt_in_in_preferences(): void
    {
        $user = User::factory()->create();
        $this->assertFalse($user->wantsEmailFor(EmailCategory::Newsletter));

        $this->actingAs($user)->post('/profile/emails', ['categories' => ['messages', 'newsletter']]);

        $this->assertTrue($user->fresh()->wantsEmailFor(EmailCategory::Newsletter));
    }

    public function test_admin_pages_are_restricted_to_admins(): void
    {
        $this->actingAs(User::factory()->create())->get('/admin/newsletter')->assertForbidden();
    }

    public function test_admin_can_create_preview_test_and_launch_a_campaign(): void
    {
        Mail::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $this->subscriber();

        $this->actingAs($admin)->get('/admin/newsletter')->assertOk()->assertSee('1 abonné(s)', false);

        $this->actingAs($admin)->post('/admin/newsletter', [
            'subject' => 'Objet test',
            'content' => 'Contenu **important**',
        ])->assertRedirect();
        $campaign = NewsletterCampaign::firstOrFail();
        $this->assertSame($admin->id, $campaign->created_by);

        $this->actingAs($admin)->get("/admin/newsletter/{$campaign->id}")->assertOk()->assertSee('Envoyer à 1 abonné(s)', false);
        $this->actingAs($admin)->get("/admin/newsletter/{$campaign->id}/apercu")->assertOk()->assertSee('>important</strong>', false);

        $this->actingAs($admin)->post("/admin/newsletter/{$campaign->id}/test")->assertSessionHas('success');
        Mail::assertSent(NewsletterMail::class, fn ($mail) => $mail->hasTo($admin->email));

        $this->actingAs($admin)->post("/admin/newsletter/{$campaign->id}/envoyer")->assertSessionHas('success');
        $this->assertSame(NewsletterCampaign::SENDING, $campaign->fresh()->status);

        $this->actingAs($admin)->put("/admin/newsletter/{$campaign->id}", ['subject' => 'Modifié', 'content' => 'x'])->assertForbidden();
        $this->actingAs($admin)->post("/admin/newsletter/{$campaign->id}/envoyer")->assertForbidden();
    }
}
