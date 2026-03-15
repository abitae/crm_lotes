<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorMembership;
use App\Models\Inmopro\CashAccount;
use App\Models\Inmopro\MembershipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproAdvisorMembershipsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(\Database\Seeders\Inmopro\AdvisorLevelSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorSeeder::class);
    }

    public function test_guests_cannot_visit_memberships_index(): void
    {
        $response = $this->get(route('inmopro.advisor-memberships.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_memberships_via_unified_advisors_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('inmopro.advisors.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('inmopro/advisors/index')->has('advisors')->has('advisorsList'));
    }

    public function test_advisor_memberships_index_redirects_to_unified_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('inmopro.advisor-memberships.index'));
        $response->assertRedirect(route('inmopro.advisors.index'));
    }

    public function test_authenticated_users_can_create_membership(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::first();
        $this->actingAs($user);

        $this->assertNotNull($advisor, 'Need an advisor');

        $response = $this->post(route('inmopro.advisor-memberships.store'), [
            'advisor_id' => $advisor->id,
            'year' => 2026,
            'amount' => 500,
        ]);

        $response->assertRedirect(route('inmopro.advisors.index'));
        $this->assertDatabaseHas('advisor_memberships', [
            'advisor_id' => $advisor->id,
            'year' => 2026,
            'amount' => 500,
        ]);
    }

    public function test_authenticated_users_can_view_membership_and_add_payment(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::first();
        $membership = AdvisorMembership::create([
            'advisor_id' => $advisor->id,
            'year' => 2026,
            'amount' => 600,
        ]);
        $this->actingAs($user);

        $response = $this->get(route('inmopro.advisor-memberships.show', $membership));
        $response->assertRedirect();
        $this->assertStringContainsString('membership_id='.$membership->id, $response->headers->get('Location'));

        $responseIndex = $this->get(route('inmopro.advisors.index', ['membership_id' => $membership->id]));
        $responseIndex->assertOk();
        $responseIndex->assertInertia(fn ($page) => $page->component('inmopro/advisors/index')->has('membershipDetail'));

        $responsePayment = $this->post(route('inmopro.advisor-memberships.payments.store', $membership), [
            'amount' => 200,
            'paid_at' => now()->format('Y-m-d'),
            'notes' => 'Primer abono',
        ]);
        $responsePayment->assertRedirect();
        $this->assertStringContainsString('membership_id='.$membership->id, $responsePayment->headers->get('Location'));
        $this->assertDatabaseHas('advisor_membership_payments', [
            'advisor_membership_id' => $membership->id,
            'amount' => 200,
            'notes' => 'Primer abono',
        ]);
    }

    public function test_authenticated_users_can_record_membership_payment_with_installment_and_cash_account(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::first();
        $type = MembershipType::create(['name' => 'Anual', 'months' => 12, 'amount' => 900]);
        $membership = AdvisorMembership::create([
            'advisor_id' => $advisor->id,
            'membership_type_id' => $type->id,
            'year' => (int) now()->format('Y'),
            'start_date' => now()->startOfYear(),
            'end_date' => now()->endOfYear(),
            'amount' => 900,
        ]);
        $installment = $membership->installments()->create([
            'sequence' => 1,
            'due_date' => now()->addMonth(),
            'amount' => 300,
            'paid_amount' => 0,
            'status' => 'PENDIENTE',
        ]);
        $cashAccount = CashAccount::create([
            'name' => 'Caja Test',
            'type' => 'CAJA',
            'currency' => 'PEN',
            'initial_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);
        $this->actingAs($user);

        $response = $this->post(route('inmopro.advisor-memberships.payments.store', $membership), [
            'advisor_membership_installment_id' => $installment->id,
            'cash_account_id' => $cashAccount->id,
            'amount' => 300,
            'paid_at' => now()->toDateString(),
            'notes' => 'Cuota 1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('advisor_membership_payments', [
            'advisor_membership_id' => $membership->id,
            'advisor_membership_installment_id' => $installment->id,
            'cash_account_id' => $cashAccount->id,
            'amount' => 300,
        ]);
        $this->assertDatabaseHas('advisor_membership_installments', [
            'id' => $installment->id,
            'paid_amount' => 300,
            'status' => 'PAGADA',
        ]);
        $this->assertDatabaseHas('cash_entries', [
            'cash_account_id' => $cashAccount->id,
            'type' => 'INGRESO',
            'amount' => 300,
        ]);
        $cashAccount->refresh();
        $this->assertSame(300.0, (float) $cashAccount->current_balance);
    }
}
