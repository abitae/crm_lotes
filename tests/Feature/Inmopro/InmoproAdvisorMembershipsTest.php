<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorMembership;
use App\Models\Inmopro\AdvisorMembershipInstallment;
use App\Models\Inmopro\AdvisorMembershipPayment;
use App\Models\Inmopro\MembershipType;
use App\Models\User;
use Database\Seeders\Inmopro\AdvisorLevelSeeder;
use Database\Seeders\Inmopro\AdvisorSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproAdvisorMembershipsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(AdvisorLevelSeeder::class);
        $this->seed(AdvisorSeeder::class);
    }

    public function test_guests_cannot_visit_memberships_index(): void
    {
        $this->get(route('inmopro.advisor-memberships.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_memberships_via_unified_advisors_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('inmopro.advisors.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('inmopro/advisors/index')
                ->has('advisors')
                ->has('advisorsList')
                ->has('membershipTypes')
                ->has('materialTypes'));
    }

    public function test_advisor_memberships_index_redirects_to_unified_page(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('inmopro.advisor-memberships.index'))
            ->assertRedirect(route('inmopro.advisors.index'));
    }

    public function test_authenticated_users_can_create_membership(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::query()->firstOrFail();
        $type = MembershipType::query()->create([
            'name' => 'Membresía create — '.__FUNCTION__,
            'months' => 12,
            'amount' => 500,
        ]);
        $this->actingAs($user);

        $this->post(route('inmopro.advisor-memberships.store'), [
            'advisor_id' => $advisor->id,
            'membership_type_id' => $type->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'amount' => 500,
        ])->assertRedirect(route('inmopro.advisors.index'));

        $this->assertDatabaseHas('advisor_memberships', [
            'advisor_id' => $advisor->id,
            'membership_type_id' => $type->id,
            'year' => 2026,
            'amount' => 500,
        ]);

        $created = AdvisorMembership::query()
            ->where('advisor_id', $advisor->id)
            ->where('year', 2026)
            ->first();
        $this->assertNotNull($created);
        $this->assertSame('2026-01-01', $created->start_date->format('Y-m-d'));
        $this->assertSame('2026-12-31', $created->end_date->format('Y-m-d'));
        $this->assertSame(
            0,
            AdvisorMembershipInstallment::query()->where('advisor_membership_id', $created->id)->count()
        );
    }

    public function test_authenticated_users_can_view_membership_add_installment_and_payment(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::query()->firstOrFail();
        $type = MembershipType::query()->create([
            'name' => 'Membresía flow — '.__FUNCTION__,
            'months' => 12,
            'amount' => 600,
        ]);
        $membership = AdvisorMembership::query()->create([
            'advisor_id' => $advisor->id,
            'membership_type_id' => $type->id,
            'year' => 2026,
            'amount' => 600,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $this->actingAs($user);

        $this->get(route('inmopro.advisor-memberships.show', $membership))
            ->assertRedirect(route('inmopro.advisors.index', ['membership_id' => $membership->id]));

        $this->get(route('inmopro.advisors.index', ['membership_id' => $membership->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('inmopro/advisors/index')->has('membershipDetail'));

        $this->post(route('inmopro.advisor-memberships.installments.store', $membership), [
            'amount' => 200,
            'due_date' => '2026-03-15',
            'notes' => null,
        ])->assertRedirect(route('inmopro.advisors.index', ['membership_id' => $membership->id]));

        $installment = AdvisorMembershipInstallment::query()
            ->where('advisor_membership_id', $membership->id)
            ->where('sequence', 1)
            ->first();
        $this->assertNotNull($installment);

        $this->post(route('inmopro.advisor-memberships.payments.store', $membership), [
            'amount' => 200,
            'paid_at' => now()->format('Y-m-d'),
            'notes' => 'Primer abono',
        ])
            ->assertRedirect(route('inmopro.advisors.index', ['membership_id' => $membership->id]))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('advisor_membership_payments', [
            'advisor_membership_id' => $membership->id,
            'advisor_membership_installment_id' => $installment->id,
            'amount' => 200,
            'notes' => 'Primer abono',
        ]);

        $installment->refresh();
        $this->assertSame('200.00', $installment->paid_amount);
        $this->assertSame('PAGADA', $installment->status);
    }

    public function test_payment_fails_when_no_payable_installment(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::query()->firstOrFail();
        $type = MembershipType::query()->create([
            'name' => 'Tipo sin cuota — '.__FUNCTION__,
            'months' => 12,
            'amount' => 100,
        ]);
        $membership = AdvisorMembership::query()->create([
            'advisor_id' => $advisor->id,
            'membership_type_id' => $type->id,
            'year' => 2026,
            'amount' => 100,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $this->actingAs($user);

        $this->from(route('inmopro.advisors.index', ['membership_id' => $membership->id]))
            ->post(route('inmopro.advisor-memberships.payments.store', $membership), [
                'amount' => 10,
                'paid_at' => '2026-04-22',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, AdvisorMembershipPayment::query()->where('advisor_membership_id', $membership->id)->count());
    }

    public function test_payment_cannot_exceed_installment_balance(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::query()->firstOrFail();
        $type = MembershipType::query()->create([
            'name' => 'Tipo tope — '.__FUNCTION__,
            'months' => 12,
            'amount' => 500,
        ]);
        $membership = AdvisorMembership::query()->create([
            'advisor_id' => $advisor->id,
            'membership_type_id' => $type->id,
            'year' => 2026,
            'amount' => 500,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $this->actingAs($user);

        $this->post(route('inmopro.advisor-memberships.installments.store', $membership), [
            'amount' => 100,
            'due_date' => '2026-06-01',
        ])->assertRedirect();

        $installment = AdvisorMembershipInstallment::query()
            ->where('advisor_membership_id', $membership->id)
            ->first();
        $this->assertNotNull($installment);

        $this->from(route('inmopro.advisors.index', ['membership_id' => $membership->id]))
            ->post(route('inmopro.advisor-memberships.payments.store', $membership), [
                'amount' => 150,
                'paid_at' => '2026-04-22',
            ])
            ->assertSessionHasErrors('amount');

        $this->assertSame(0, AdvisorMembershipPayment::query()->where('advisor_membership_id', $membership->id)->count());
        $installment->refresh();
        $this->assertSame('0.00', $installment->paid_amount);
    }

    public function test_authenticated_users_can_update_membership_amount_and_dates(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::query()->firstOrFail();
        $type = MembershipType::query()->create([
            'name' => 'Membresía update — '.__FUNCTION__,
            'months' => 12,
            'amount' => 500,
        ]);
        $membership = AdvisorMembership::query()->create([
            'advisor_id' => $advisor->id,
            'membership_type_id' => $type->id,
            'year' => 2026,
            'amount' => 500,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $this->actingAs($user);

        $this->put(route('inmopro.advisor-memberships.update', $membership), [
            'amount' => 550,
            'start_date' => '2026-02-01',
            'end_date' => '2027-01-31',
        ])->assertRedirect();

        $membership->refresh();
        $this->assertSame('550.00', $membership->amount);
        $this->assertSame('2026-02-01', $membership->start_date->format('Y-m-d'));
        $this->assertSame('2027-01-31', $membership->end_date->format('Y-m-d'));
    }
}
