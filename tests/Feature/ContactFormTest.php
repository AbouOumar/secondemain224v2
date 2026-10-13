<?php

namespace Tests\Feature;

use App\Mail\ContactMessageMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactFormTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config(['legal.public_email' => 'contact@seconde-main.test']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Awa Diallo',
            'email' => 'awa@example.com',
            'message' => 'Je ne reçois pas mon code de livraison.',
        ], $overrides);
    }

    public function test_api_contact_sends_the_message_to_the_public_address(): void
    {
        $this->postJson('/api/v1/contact', $this->payload())
            ->assertOk()
            ->assertJsonPath('message', 'Message envoyé avec succès.');

        Mail::assertSent(ContactMessageMail::class, function (ContactMessageMail $mail) {
            return $mail->hasTo('contact@seconde-main.test')
                && $mail->hasReplyTo('awa@example.com')
                && $mail->senderName === 'Awa Diallo'
                && str_contains($mail->body, 'code de livraison');
        });
    }

    public function test_api_contact_validates_the_fields(): void
    {
        $this->postJson('/api/v1/contact', [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'email', 'message']);

        $this->postJson('/api/v1/contact', $this->payload(['email' => 'pas-un-email']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);

        $this->postJson('/api/v1/contact', $this->payload(['message' => str_repeat('a', 5001)]))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message']);

        Mail::assertNothingSent();
    }

    public function test_api_contact_is_unavailable_when_no_public_address_is_configured(): void
    {
        config(['legal.public_email' => null]);

        $this->postJson('/api/v1/contact', $this->payload())->assertStatus(503);

        Mail::assertNothingSent();
    }

    public function test_api_contact_is_rate_limited(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/v1/contact', $this->payload())->assertOk();
        }

        $this->postJson('/api/v1/contact', $this->payload())->assertStatus(429);
    }

    public function test_web_contact_form_really_sends_the_message(): void
    {
        $this->post('/contact', $this->payload())->assertRedirect();

        Mail::assertSent(ContactMessageMail::class, fn (ContactMessageMail $mail) => $mail->hasTo('contact@seconde-main.test'));
    }
}
