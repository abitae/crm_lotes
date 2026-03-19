<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorMembership;
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
        $response->assertInertia(fn ($page) => $page
            ->component('inmopro/advisors/index')
            ->has('advisors')
            ->has('advisorsList')
            ->has('membershipTypes'));
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
        $type = MembershipType::create([
            'name' => 'Membresía 2026',
            'months' => 12,
            'amount' => 500,
        ]);
        $this->actingAs($user);

        $this->assertNotNull($advisor, 'Need an advisor');

        $response = $this->post(route('inmopro.advisor-memberships.store'), [
            'advisor_id' => $advisor->id,
            'membership_type_id' => $type->id,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'amount' => 500,
        ]);

        $response->assertRedirect(route('inmopro.advisors.index'));
        $this->assertDatabaseHas('advisor_memberships', [
            'advisor_id' => $advisor->id,
            'membership_type_id' => $type->id,
            'year' => 2026,
            'amount' => 500,
        ]);
        $created = AdvisorMembership::where('advisor_id', $advisor->id)->where('year', 2026)->first();
        $this->assertNotNull($created);
        $this->assertSame('2026-01-01', $created->start_date->format('Y-m-d'));
        $this->assertSame('2026-12-31', $created->end_date->format('Y-m-d'));
    }

    public function test_authenticated_users_can_view_membership_and_add_payment(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::first();
        $type = MembershipType::create([
            'name' => 'Membresía 2026',
            'months' => 12,
            'amount' => 600,
        ]);
        $membership = AdvisorMembership::create([
            'advisor_id' => $advisor->id,
            'membership_type_id' => $type->id,
            'year' => 2026,
            'amount' => 600,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
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

    public function test_authenticated_users_can_update_membership_amount_and_dates(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::first();
        $type = MembershipType::create([
            'name' => 'Membresía 2026',
            'months' => 12,
            'amount' => 500,
        ]);
        $membership = AdvisorMembership::create([
            'advisor_id' => $advisor->id,
            'membership_type_id' => $type->id,
            'year' => 2026,
            'amount' => 500,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);
        $this->actingAs($user);

        $response = $this->put(route('inmopro.advisor-memberships.update', $membership), [
            'amount' => 550,
            'start_date' => '2026-02-01',
            'end_date' => '2027-01-31',
        ]);

        $response->assertRedirect();
        $membership->refresh();
        $this->assertSame('550.00', $membership->amount);
        $this->assertSame('2026-02-01', $membership->start_date->format('Y-m-d'));
        $this->assertSame('2027-01-31', $membership->end_date->format('Y-m-d'));
    }
}
