<?php

namespace Tests\Feature;

use App\Models\Notification;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use App\Models\WithdrawalRequest;
use App\Services\Account\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class WalletWithdrawalTest extends TestCase
{
    use RefreshDatabase;

    private function userWithBalance(int $balance): User
    {
        $user = User::factory()->create(['role' => 'vendeur']);
        Wallet::create(['user_id' => $user->id, 'balance' => $balance, 'currency' => 'GNF']);

        return $user;
    }

    private function request_(User $user, int $montant = 20000, array $over = [])
    {
        return $this->postJson('/api/v1/wallet/withdrawals', array_merge(['montant' => $montant, 'phone' => '622000000', 'operator' => 'orange_money'], $over));
    }

    public function test_a_withdrawal_needs_authentication_and_valid_data(): void
    {
        $this->postJson('/api/v1/wallet/withdrawals', [])->assertUnauthorized();

        $user = $this->userWithBalance(100000);
        Sanctum::actingAs($user);
        $this->request_($user, 500)->assertUnprocessable()->assertJsonValidationErrors('montant');
        $this->request_($user, 20000, ['operator' => 'virement'])->assertUnprocessable()->assertJsonValidationErrors('operator');
        $this->request_($user, 20000, ['phone' => ''])->assertUnprocessable()->assertJsonValidationErrors('phone');
        $this->assertSame(0, WithdrawalRequest::count());
    }

    public function test_the_amount_is_debited_at_once_and_the_request_waits_for_the_team(): void
    {
        $user = $this->userWithBalance(50000);
        Sanctum::actingAs($user);

        $this->request_($user)->assertCreated()->assertJsonPath('data.status', 'en_attente')->assertJsonPath('data.montant', 20000);

        $this->assertEquals(30000, $user->wallet()->first()->balance);
        $this->assertSame(1, Transaction::where('type', 'debit')->where('source', 'retrait')->count());
        $this->assertSame('622000000', WithdrawalRequest::first()->phone);
    }

    public function test_a_withdrawal_above_the_balance_is_refused(): void
    {
        $user = $this->userWithBalance(15000);
        Sanctum::actingAs($user);

        $this->request_($user, 20000)->assertStatus(422);

        $this->assertEquals(15000, $user->wallet()->first()->balance);
        $this->assertSame(0, WithdrawalRequest::count());
    }

    public function test_only_one_withdrawal_can_wait_at_a_time(): void
    {
        $user = $this->userWithBalance(100000);
        Sanctum::actingAs($user);

        $this->request_($user)->assertCreated();
        $this->request_($user)->assertStatus(409);

        $this->assertEquals(80000, $user->wallet()->first()->balance);
    }

    public function test_the_list_only_shows_my_requests(): void
    {
        $mine = $this->userWithBalance(100000);
        $other = $this->userWithBalance(100000);
        Sanctum::actingAs($other);
        $this->request_($other)->assertCreated();
        Sanctum::actingAs($mine);
        $this->request_($mine)->assertCreated();

        $data = $this->getJson('/api/v1/wallet/withdrawals')->assertOk()->json('data');

        $this->assertCount(1, $data);
    }

    public function test_the_admin_marks_a_withdrawal_as_paid_once(): void
    {
        $user = $this->userWithBalance(50000);
        Sanctum::actingAs($user);
        $this->request_($user)->assertCreated();
        $withdrawal = WithdrawalRequest::first();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.withdrawals.pay', $withdrawal), ['note' => 'OM ref 123'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.withdrawals.pay', $withdrawal))->assertRedirect();

        $fresh = $withdrawal->fresh();
        $this->assertSame('paye', $fresh->status);
        $this->assertSame('OM ref 123', $fresh->note);
        $this->assertSame($admin->id, $fresh->processed_by);
        $this->assertEquals(30000, $user->wallet()->first()->balance); // rien de plus débité ni recrédité
        $this->assertSame(1, Notification::where('user_id', $user->id)->where('type', 'retrait.traite')->count());
    }

    public function test_the_admin_rejects_a_withdrawal_and_the_money_comes_back_once(): void
    {
        $user = $this->userWithBalance(50000);
        Sanctum::actingAs($user);
        $this->request_($user)->assertCreated();
        $withdrawal = WithdrawalRequest::first();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.withdrawals.reject', $withdrawal), ['note' => 'Numéro invalide'])->assertRedirect();
        $this->actingAs($admin)->post(route('admin.withdrawals.reject', $withdrawal))->assertRedirect();

        $this->assertSame('refuse', $withdrawal->fresh()->status);
        $this->assertEquals(50000, $user->wallet()->first()->balance);
        $this->assertSame(1, Transaction::where('type', 'credit')->where('source', 'remboursement')->count());
    }

    public function test_a_paid_withdrawal_cannot_be_rejected_afterwards(): void
    {
        $user = $this->userWithBalance(50000);
        Sanctum::actingAs($user);
        $this->request_($user)->assertCreated();
        $withdrawal = WithdrawalRequest::first();
        $admin = User::factory()->create(['role' => 'admin']);

        $this->actingAs($admin)->post(route('admin.withdrawals.pay', $withdrawal));
        $this->actingAs($admin)->post(route('admin.withdrawals.reject', $withdrawal));

        $this->assertSame('paye', $withdrawal->fresh()->status);
        $this->assertEquals(30000, $user->wallet()->first()->balance);
    }

    public function test_only_admins_reach_the_withdrawal_pages(): void
    {
        $user = $this->userWithBalance(50000);
        Sanctum::actingAs($user);
        $this->request_($user)->assertCreated();
        $withdrawal = WithdrawalRequest::first();

        $this->actingAs($user)->get(route('admin.withdrawals.index'))->assertForbidden();
        $this->actingAs($user)->post(route('admin.withdrawals.pay', $withdrawal))->assertForbidden();
        $this->assertSame('en_attente', $withdrawal->fresh()->status);

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('admin.withdrawals.index'))->assertOk()->assertSee('622000000');
    }

    public function test_a_waiting_withdrawal_blocks_account_deletion(): void
    {
        $user = $this->userWithBalance(50000);
        Sanctum::actingAs($user);
        $this->request_($user)->assertCreated();

        $blockers = app(AccountDeletionService::class)->blockers($user->fresh());

        $this->assertNotEmpty($blockers);
        $this->assertStringContainsString('retrait', implode(' ', $blockers));
    }
}
