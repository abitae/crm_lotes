<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorMembership;
use App\Models\Inmopro\MembershipType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproMembershipTypesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(\Database\Seeders\Inmopro\AdvisorLevelSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorSeeder::class);
    }

    public function test_guests_cannot_visit_membership_types_index(): void
    {
        $response = $this->get(route('inmopro.membership-types.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_membership_types_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('inmopro.membership-types.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('inmopro/membership-types/index')->has('membershipTypes'));
    }

    public function test_authenticated_users_can_create_membership_type(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post(route('inmopro.membership-types.store'), [
            'name' => 'Membresía 2026',
            'months' => 12,
            'amount' => 500,
        ]);

        $response->assertRedirect(route('inmopro.membership-types.index'));
        $this->assertDatabaseHas('membership_types', [
            'name' => 'Membresía 2026',
            'months' => 12,
            'amount' => 500,
        ]);
    }

    public function test_authenticated_users_can_update_membership_type(): void
    {
        $user = User::factory()->create();
        $type = MembershipType::create([
            'name' => 'Membresía 2025',
            'months' => 12,
            'amount' => 400,
        ]);
        $this->actingAs($user);

        $response = $this->put(route('inmopro.membership-types.update', $type), [
            'name' => 'Membresía 2025 actualizada',
            'months' => 12,
            'amount' => 450,
        ]);

        $response->assertRedirect(route('inmopro.membership-types.index'));
        $this->assertDatabaseHas('membership_types', [
            'id' => $type->id,
            'name' => 'Membresía 2025 actualizada',
            'amount' => 450,
        ]);
    }

    public function test_authenticated_users_can_delete_membership_type(): void
    {
        $user = User::factory()->create();
        $type = MembershipType::create([
            'name' => 'Membresía 2025',
            'months' => 12,
            'amount' => 400,
        ]);
        $this->actingAs($user);

        $response = $this->delete(route('inmopro.membership-types.destroy', $type));

        $response->assertRedirect(route('inmopro.membership-types.index'));
        $this->assertDatabaseMissing('membership_types', ['id' => $type->id]);
    }

    public function test_authenticated_users_can_visit_bulk_assign_page(): void
    {
        $user = User::factory()->create();
        $type = MembershipType::create([
            'name' => 'Membresía 2026',
            'months' => 12,
            'amount' => 500,
        ]);
        $this->actingAs($user);

        $response = $this->get(route('inmopro.membership-types.bulk-assign', $type));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('inmopro/membership-types/bulk-assign')
            ->has('membershipType')
            ->has('advisors')
            ->has('alreadyAssignedIds'));
    }

    public function test_bulk_assign_creates_memberships_for_selected_advisors(): void
    {
        $user = User::factory()->create();
        $advisor1 = Advisor::first();
        $advisor2 = Advisor::skip(1)->first();
        $this->assertNotNull($advisor1);
        $this->assertNotNull($advisor2);

        $type = MembershipType::create([
            'name' => 'Membresía 2026',
            'months' => 12,
            'amount' => 500,
        ]);
        $this->actingAs($user);

        $response = $this->post(route('inmopro.membership-types.bulk-assign.store', $type), [
            'advisor_ids' => [$advisor1->id, $advisor2->id],
            'start_date' => '2026-01-01',
        ]);

        $response->assertRedirect(route('inmopro.membership-types.index'));
        $this->assertDatabaseCount('advisor_memberships', 2);
        $this->assertDatabaseHas('advisor_memberships', [
            'advisor_id' => $advisor1->id,
            'membership_type_id' => $type->id,
            'year' => 2026,
            'amount' => 500,
        ]);
        $this->assertDatabaseHas('advisor_memberships', [
            'advisor_id' => $advisor2->id,
            'membership_type_id' => $type->id,
            'year' => 2026,
            'amount' => 500,
        ]);
    }

    public function test_bulk_assign_skips_advisors_who_already_have_this_membership_type(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::first();
        $this->assertNotNull($advisor);

        $type = MembershipType::create([
            'name' => 'Membresía 2026',
            'months' => 12,
            'amount' => 500,
        ]);
        AdvisorMembership::create([
            'advisor_id' => $advisor->id,
            'membership_type_id' => $type->id,
            'year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'amount' => 300,
        ]);
        $this->actingAs($user);

        $response = $this->post(route('inmopro.membership-types.bulk-assign.store', $type), [
            'advisor_ids' => [$advisor->id],
            'start_date' => '2026-06-01',
        ]);

        $response->assertRedirect(route('inmopro.membership-types.index'));
        $this->assertDatabaseCount('advisor_memberships', 1);
    }
}
