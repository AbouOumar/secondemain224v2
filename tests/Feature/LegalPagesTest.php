<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    public function test_privacy_policy_page_is_public(): void
    {
        $this->get('/confidentialite')
            ->assertOk()
            ->assertSee('Politique de confidentialité')
            ->assertSee('Connexion avec Google');
    }

    public function test_terms_page_shows_current_marketplace_rules(): void
    {
        config(['marketplace.escrow_auto_release_delivered_hours' => 72]);

        $this->get('/cgu')
            ->assertOk()
            ->assertSee("Conditions générales d'utilisation", false)
            ->assertSee('72 heures après la livraison')
            ->assertSee('expire après 48 heures');
    }

    public function test_legal_pages_are_linked_from_footer_and_register_page(): void
    {
        $this->get('/register')
            ->assertSee(route('legal.terms'))
            ->assertSee(route('legal.privacy'));
    }
}
