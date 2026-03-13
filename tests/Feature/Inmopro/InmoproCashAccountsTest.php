<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\CashAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproCashAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_create_cash_account_and_manual_entry(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->post(route('inmopro.cash-accounts.store'), [
            'name' => 'Banco BCP',
            'type' => 'BANCO',
            'currency' => 'PEN',
            'initial_balance' => 500,
            'is_active' => true,
        ])->assertRedirect();

        $account = CashAccount::firstOrFail();

        $this->post(route('inmopro.cash-accounts.entries.store', $account), [
            'type' => 'EGRESO',
            'concept' => 'Pago notaria',
            'amount' => 125,
            'entry_date' => now()->toDateString(),
            'reference' => 'FAC-1',
        ])->assertRedirect();

        $this->assertDatabaseHas('cash_accounts', [
            'id' => $account->id,
            'current_balance' => 375,
        ]);
        $this->assertDatabaseHas('cash_entries', [
            'cash_account_id' => $account->id,
            'type' => 'EGRESO',
            'amount' => 125,
        ]);
    }
}
