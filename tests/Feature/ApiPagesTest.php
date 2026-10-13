<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiPagesTest extends TestCase
{
    public function test_terms_are_served_as_a_fragment_with_the_current_rules(): void
    {
        config([
            'marketplace.escrow_auto_release_delivered_hours' => 72,
            'legal.company_name' => 'Seconde Main SARLU',
            'legal.updated_at' => '2 octobre 2026',
        ]);

        $this->getJson('/api/v1/pages/terms')
            ->assertOk()
            ->assertJsonPath('data.slug', 'terms')
            ->assertJsonPath('data.title', "Conditions générales d'utilisation")
            ->assertJsonPath('data.updated_at', '2 octobre 2026')
            ->assertJsonPath('data.html', fn (string $html) => str_contains($html, '72 heures après la livraison')
                && str_contains($html, 'Seconde Main SARLU')
                && ! str_contains($html, '<h1')
                && ! str_contains($html, '<html'));
    }

    public function test_every_information_page_is_available(): void
    {
        $expected = [
            'privacy' => 'Connexion avec Google',
            'account-deletion' => 'Compte &gt; Supprimer mon compte',
            'about' => 'Notre mission',
        ];

        foreach ($expected as $slug => $text) {
            $this->getJson("/api/v1/pages/{$slug}")
                ->assertOk()
                ->assertJsonPath('data.slug', $slug)
                ->assertJsonPath('data.html', fn (string $html) => str_contains($html, $text));
        }
    }

    public function test_unknown_page_is_not_found(): void
    {
        $this->getJson('/api/v1/pages/admin')->assertNotFound();
    }

    public function test_about_page_only_shows_contact_details_that_are_configured(): void
    {
        config(['legal.public_phone' => null, 'legal.public_email' => 'contact@seconde-main.test']);

        $html = $this->getJson('/api/v1/pages/about')->json('data.html');

        $this->assertStringContainsString('contact@seconde-main.test', $html);
        $this->assertStringNotContainsString('tel:', $html);
    }
}
