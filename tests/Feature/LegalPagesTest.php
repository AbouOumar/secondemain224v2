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

    public function test_account_deletion_page_is_public_and_explains_the_steps(): void
    {
        $this->get('/suppression-compte')
            ->assertOk()
            ->assertSee('Supprimer mon compte')
            ->assertSee('Compte &gt; Supprimer mon compte', false)
            ->assertSee(route('contact'))
            ->assertSee(route('legal.privacy'));
    }

    public function test_privacy_policy_links_to_account_deletion(): void
    {
        $this->get('/confidentialite')->assertSee(route('legal.account-deletion'));
    }

    public function test_legal_pages_are_linked_from_footer_and_register_page(): void
    {
        $this->get('/register')
            ->assertSee(route('legal.terms'))
            ->assertSee(route('legal.privacy'));
    }
}
