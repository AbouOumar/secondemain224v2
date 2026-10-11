<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebLogoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_post_logout_signs_the_user_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('logout'))->assertRedirect('/');

        $this->assertGuest();
    }

    public function test_get_logout_no_longer_signs_the_user_out(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/logout')->assertRedirect('/');

        $this->assertAuthenticatedAs($user);
    }

    public function test_navbar_offers_a_logout_form_not_a_link(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/')
            ->assertOk()
            ->assertSee('action="'.route('logout').'"', false);
    }
}
