<?php

namespace Tests\Feature;

use App\Enums\EmailCategory;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Services\Chat\ChatService;
use App\Services\Notification\FirebaseNotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class ActivityEmailsTest extends TestCase
{
    use RefreshDatabase;

    private function notify(User $user, string $type = 'nouvelle_offre'): void
    {
        app(FirebaseNotificationService::class)->send($user, 'Nouvelle offre reçue', 'Awa propose 50 000 GNF pour « Vélo ».', $type);
    }

    private function unsubscribeUrl(User $user, EmailCategory $category): string
    {
        return URL::signedRoute('email.unsubscribe', ['user' => $user->id, 'category' => $category->value]);
    }

    public function test_platform_notification_is_also_sent_by_email(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->notify($user);

        $this->assertDatabaseHas('notifications', ['user_id' => $user->id, 'type' => 'nouvelle_offre']);
        Notification::assertSentTo($user, ActivityNotification::class, fn ($notification) => $notification->category === EmailCategory::Offres
            && $notification->title === 'Nouvelle offre reçue');
    }

    public function test_no_email_to_unconfirmed_address_or_disabled_category_or_unmapped_type(): void
    {
        Notification::fake();
        $unverified = User::factory()->unverified()->create();
        $optedOut = User::factory()->create();
        $optedOut->setEmailPreference(EmailCategory::Offres, false);
        $other = User::factory()->create();

        $this->notify($unverified);
        $this->notify($optedOut);
        $this->notify($other, 'push');

        Notification::assertNothingSent();
        $this->assertDatabaseCount('notifications', 3);
    }

    public function test_disabling_one_category_keeps_the_others(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $user->setEmailPreference(EmailCategory::Offres, false);

        $this->notify($user, 'livraison.livree');

        Notification::assertSentTo($user, ActivityNotification::class, fn ($notification) => $notification->category === EmailCategory::Livraisons);
    }

    public function test_new_message_email_is_limited_to_one_per_conversation_per_hour(): void
    {
        Notification::fake();
        $sender = User::factory()->create(['name' => 'Awa']);
        $receiver = User::factory()->create();
        $chat = app(ChatService::class);

        $chat->sendMessage($sender, $receiver, 'Bonjour, le vélo est toujours disponible ?');
        $chat->sendMessage($sender, $receiver, 'Je peux passer demain.');

        Notification::assertSentToTimes($receiver, ActivityNotification::class, 1);
        Notification::assertSentTo($receiver, ActivityNotification::class, fn ($notification) => $notification->title === 'Nouveau message de Awa'
            && str_contains($notification->body, 'toujours disponible')
            && $notification->actionUrl === route('messages.show', ['user' => $sender->id]));

        $this->travel(61)->minutes();
        $chat->sendMessage($sender, $receiver, 'Vous êtes là ?');

        Notification::assertSentToTimes($receiver, ActivityNotification::class, 2);
    }

    public function test_sent_email_contains_unsubscribe_link_and_one_click_header(): void
    {
        config(['mail.default' => 'array']);
        $user = User::factory()->create();

        $this->notify($user);

        $messages = app('mailer')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $email = $messages->first()->getOriginalMessage();
        $this->assertSame('Nouvelle offre reçue', $email->getSubject());
        $this->assertStringContainsString('/emails/desabonnement/'.$user->id.'/offres', $email->getHeaders()->get('List-Unsubscribe')->getBodyAsString());
        $this->assertSame('List-Unsubscribe=One-Click', $email->getHeaders()->get('List-Unsubscribe-Post')->getBodyAsString());
        $this->assertStringContainsString('Ne plus recevoir ces e-mails', $email->getHtmlBody());
    }

    public function test_unsubscribe_link_asks_confirmation_then_disables_category(): void
    {
        $user = User::factory()->create();
        $url = $this->unsubscribeUrl($user, EmailCategory::Offres);

        $this->get($url)->assertOk()->assertSee('Confirmer la désinscription');
        $this->assertTrue($user->fresh()->wantsEmailFor(EmailCategory::Offres));

        $this->post($url)->assertOk()->assertSee('est noté', false);
        $this->assertFalse($user->fresh()->wantsEmailFor(EmailCategory::Offres));
        $this->assertTrue($user->fresh()->wantsEmailFor(EmailCategory::Messages));
    }

    public function test_unsubscribe_link_without_valid_signature_is_rejected(): void
    {
        $user = User::factory()->create();
        $victim = User::factory()->create();

        $this->post('/emails/desabonnement/'.$victim->id.'/offres')->assertForbidden();
        $this->post(str_replace('/'.$user->id.'/', '/'.$victim->id.'/', $this->unsubscribeUrl($user, EmailCategory::Offres)))->assertForbidden();

        $this->assertTrue($victim->fresh()->wantsEmailFor(EmailCategory::Offres));
    }

    public function test_user_can_update_preferences_from_profile_page(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/profile/emails')->assertOk()->assertSee('Offres et négociations');
        $this->actingAs($user)->post('/profile/emails', ['categories' => ['messages', 'commandes']])->assertRedirect();

        $user->refresh();
        $this->assertTrue($user->wantsEmailFor(EmailCategory::Messages));
        $this->assertTrue($user->wantsEmailFor(EmailCategory::Commandes));
        $this->assertFalse($user->wantsEmailFor(EmailCategory::Offres));
        $this->assertFalse($user->wantsEmailFor(EmailCategory::Livraisons));
        $this->assertFalse($user->wantsEmailFor(EmailCategory::Alertes));
    }

    public function test_user_can_read_and_update_preferences_through_api(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user, 'sanctum')->getJson('/api/v1/profile/email-preferences')
            ->assertOk()
            ->assertJsonPath('data.email_verified', true)
            ->assertJsonCount(5, 'data.categories');

        $this->actingAs($user, 'sanctum')->putJson('/api/v1/profile/email-preferences', ['categories' => ['offres' => false]])
            ->assertOk()
            ->assertJsonPath('data.categories.1.key', 'offres')
            ->assertJsonPath('data.categories.1.enabled', false)
            ->assertJsonPath('data.categories.0.enabled', true);

        $this->actingAs($user, 'sanctum')->putJson('/api/v1/profile/email-preferences', ['categories' => ['spam' => true]])
            ->assertUnprocessable();
    }
}
