<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\RoleRequest;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RoleRequestTest extends TestCase
{
    use RefreshDatabase;

    private function registerPayload(string $role, string $phone = '622000001'): array
    {
        return ['name' => 'Awa', 'phone' => $phone, 'password' => 'secret-123', 'password_confirmation' => 'secret-123', 'role' => $role];
    }

    private function motardDetails(array $over = []): array
    {
        return array_merge(['vehicle' => 'Moto', 'plate' => 'RC-1234-A', 'zone' => 'Kaloum, Matam'], $over);
    }

    private function proDetails(array $over = []): array
    {
        return array_merge(['business_name' => 'Chez Awa', 'address' => 'Madina marché'], $over);
    }

    // --- inscription ---------------------------------------------------------------------------

    public function test_registering_as_buyer_or_seller_gives_that_role_at_once(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registerPayload('vendeur'))->assertCreated()->assertJsonPath('user.role', 'vendeur');

        $this->assertSame(0, RoleRequest::count());
    }

    public function test_registering_as_rider_or_reseller_creates_a_buyer_and_a_pending_request(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registerPayload('motard'))->assertCreated()->assertJsonPath('user.role', 'acheteur');
        $this->postJson('/api/v1/auth/register', $this->registerPayload('revendeur_pro', '622000002'))->assertCreated()->assertJsonPath('user.role', 'acheteur');

        $this->assertSame(['motard', 'revendeur_pro'], RoleRequest::orderBy('id')->pluck('role')->all());
        $this->assertSame(['en_attente', 'en_attente'], RoleRequest::pluck('status')->all());
        $this->assertSame(0, User::whereIn('role', ['motard', 'revendeur_pro'])->count());
    }

    public function test_the_web_registration_follows_the_same_rule(): void
    {
        $this->post('/register', $this->registerPayload('motard'));

        $user = User::where('phone', '622000001')->firstOrFail();
        $this->assertSame('acheteur', $user->role->value);
        $this->assertSame(1, RoleRequest::where('user_id', $user->id)->where('role', 'motard')->where('status', 'en_attente')->count());
    }

    public function test_nobody_can_register_as_admin(): void
    {
        $this->postJson('/api/v1/auth/register', $this->registerPayload('admin'))->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->assertSame(0, User::count());
    }

    public function test_the_profile_update_cannot_change_the_role(): void
    {
        $user = User::factory()->create(['role' => 'acheteur']);
        Sanctum::actingAs($user);

        $this->putJson('/api/v1/profile', ['name' => 'Nouveau nom', 'role' => 'admin'])->assertOk();

        $this->assertSame('acheteur', $user->fresh()->role->value);
    }

    // --- demande depuis le compte --------------------------------------------------------------

    public function test_a_buyer_asks_to_become_a_rider_with_the_needed_details(): void
    {
        $user = User::factory()->create(['role' => 'acheteur']);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/role-request', ['role' => 'motard', 'details' => $this->motardDetails()])
            ->assertCreated()
            ->assertJsonPath('data.status', 'en_attente')
            ->assertJsonPath('data.details.zone', 'Kaloum, Matam');

        $this->assertSame('acheteur', $user->fresh()->role->value);
    }

    public function test_the_details_depend_on_the_role(): void
    {
        $user = User::factory()->create(['role' => 'vendeur']);
        Sanctum::actingAs($user);

        $this->postJson('/api/v1/role-request', ['role' => 'motard', 'details' => ['vehicle' => 'Moto']])->assertUnprocessable()->assertJsonValidationErrors('details.zone');
        $this->postJson('/api/v1/role-request', ['role' => 'revendeur_pro', 'details' => ['address' => 'Madina']])->assertUnprocessable()->assertJsonValidationErrors('details.business_name');
        $this->postJson('/api/v1/role-request', ['role' => 'admin', 'details' => []])->assertUnprocessable()->assertJsonValidationErrors('role');
        $this->assertSame(0, RoleRequest::count());

        $this->postJson('/api/v1/role-request', ['role' => 'revendeur_pro', 'details' => $this->proDetails()])->assertCreated();
    }

    public function test_one_pending_request_at_a_time_and_none_for_a_role_already_held(): void
    {
        $user = User::factory()->create(['role' => 'acheteur']);
        Sanctum::actingAs($user);
        $this->postJson('/api/v1/role-request', ['role' => 'motard', 'details' => $this->motardDetails()])->assertCreated();
        $this->postJson('/api/v1/role-request', ['role' => 'revendeur_pro', 'details' => $this->proDetails()])->assertStatus(409);

        $rider = User::factory()->create(['role' => 'motard']);
        Sanctum::actingAs($rider);
        $this->postJson('/api/v1/role-request', ['role' => 'motard', 'details' => $this->motardDetails()])->assertForbidden();
    }

    public function test_the_current_request_is_reported_and_can_be_withdrawn(): void
    {
        $user = User::factory()->create(['role' => 'acheteur']);
        Sanctum::actingAs($user);

        $this->getJson('/api/v1/role-request')->assertOk()->assertJsonPath('data', null);

        $this->postJson('/api/v1/role-request', ['role' => 'motard', 'details' => $this->motardDetails()])->assertCreated();
        $this->getJson('/api/v1/role-request')->assertOk()->assertJsonPath('data.role', 'motard');

        $this->deleteJson('/api/v1/role-request')->assertNoContent();
        $this->assertSame(0, RoleRequest::where('status', 'en_attente')->count());
        $this->postJson('/api/v1/role-request', ['role' => 'revendeur_pro', 'details' => $this->proDetails()])->assertCreated();
    }

    // --- administration ------------------------------------------------------------------------

    private function pendingFor(string $role = 'motard'): RoleRequest
    {
        $user = User::factory()->create(['role' => 'acheteur']);
        Sanctum::actingAs($user);
        $details = $role === 'motard' ? $this->motardDetails() : $this->proDetails();
        $this->postJson('/api/v1/role-request', ['role' => $role, 'details' => $details])->assertCreated();

        return RoleRequest::where('user_id', $user->id)->firstOrFail();
    }

    public function test_the_admin_approves_a_rider_request_once(): void
    {
        $request = $this->pendingFor('motard');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.role-requests.approve', $request))->assertRedirect();
        $this->actingAs($admin)->post(route('admin.role-requests.approve', $request))->assertRedirect();

        $user = $request->user->fresh();
        $this->assertSame('motard', $user->role->value);
        $this->assertSame('hors_ligne', $user->rider_status);
        $this->assertSame('approuve', $request->fresh()->status);
        $this->assertSame($admin->id, $request->fresh()->processed_by);
        $this->assertSame(1, Notification::where('user_id', $user->id)->where('type', 'role.traite')->count());
    }

    public function test_approving_a_reseller_gives_the_role_and_a_wallet(): void
    {
        $request = $this->pendingFor('revendeur_pro');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.role-requests.approve', $request))->assertRedirect();

        $this->assertSame('revendeur_pro', $request->user->fresh()->role->value);
        $this->assertSame(1, Wallet::where('user_id', $request->user_id)->count());
    }

    public function test_the_admin_rejects_with_a_note_and_the_role_does_not_change(): void
    {
        $request = $this->pendingFor('motard');
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.role-requests.reject', $request), ['note' => 'Pièce illisible'])->assertRedirect();

        $this->assertSame('acheteur', $request->user->fresh()->role->value);
        $this->assertSame('refuse', $request->fresh()->status);
        $this->assertSame('Pièce illisible', $request->fresh()->note);
        $this->assertSame(1, Notification::where('user_id', $request->user_id)->where('type', 'role.traite')->count());
    }

    public function test_a_rejected_user_can_ask_again_and_an_approved_request_cannot_be_rejected(): void
    {
        $request = $this->pendingFor('motard');
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->post(route('admin.role-requests.reject', $request), ['note' => 'x']);

        Sanctum::actingAs($request->user->fresh());
        $this->postJson('/api/v1/role-request', ['role' => 'motard', 'details' => $this->motardDetails()])->assertCreated();

        $second = RoleRequest::where('user_id', $request->user_id)->where('status', 'en_attente')->firstOrFail();
        $this->actingAs($admin)->post(route('admin.role-requests.approve', $second));
        $this->actingAs($admin)->post(route('admin.role-requests.reject', $second));
        $this->assertSame('approuve', $second->fresh()->status);
        $this->assertSame('motard', $request->user->fresh()->role->value);
    }

    public function test_only_admins_reach_the_request_pages(): void
    {
        $request = $this->pendingFor('motard');
        $user = $request->user;

        $this->actingAs($user)->get(route('admin.role-requests.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.role-requests.approve', $request))->assertForbidden();
        $this->assertSame('acheteur', $user->fresh()->role->value);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.role-requests.index'))->assertOk()->assertSee('Kaloum, Matam');
    }
}
