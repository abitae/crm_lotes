<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorLevel;
use App\Models\Inmopro\AdvisorMembership;
use App\Models\Inmopro\MembershipType;
use App\Models\Inmopro\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InmoproAdvisorsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Inmopro\TeamSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorLevelSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\LotStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CommissionStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ProjectSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorSeeder::class);
    }

    public function test_guests_cannot_visit_advisors_index(): void
    {
        $response = $this->get(route('inmopro.advisors.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_advisors_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('inmopro.advisors.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('inmopro/advisors/index')->has('advisors')->has('teams'));
    }

    public function test_authenticated_users_can_create_advisor(): void
    {
        $user = User::factory()->create();
        $level = AdvisorLevel::first();
        $team = Team::first();
        $this->actingAs($user);

        $response = $this->post(route('inmopro.advisors.store'), [
            'name' => 'Asesor Nuevo Test',
            'phone' => '999888777',
            'email' => 'asesor@example.com',
            'username' => 'asesor_nuevo',
            'pin' => '654321',
            'team_id' => $team->id,
            'advisor_level_id' => $level->id,
            'personal_quota' => 10,
        ]);

        $response->assertRedirect(route('inmopro.advisors.index'));
        $this->assertDatabaseHas('advisors', [
            'name' => 'Asesor Nuevo Test',
            'email' => 'asesor@example.com',
            'team_id' => $team->id,
            'username' => 'asesor_nuevo',
        ]);

        $advisor = Advisor::where('username', 'asesor_nuevo')->firstOrFail();
        $this->assertTrue(Hash::check('654321', (string) $advisor->pin));
    }

    public function test_authenticated_users_can_assign_membership_with_installments_from_advisor(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::first();
        $type = MembershipType::create([
            'name' => 'Anual Test',
            'months' => 12,
            'amount' => 1200,
        ]);
        $this->actingAs($user);

        $startDate = now()->startOfMonth()->toDateString();
        $response = $this->post(route('inmopro.advisors.memberships.store', $advisor), [
            'membership_type_id' => $type->id,
            'start_date' => $startDate,
            'installments_count' => 3,
        ]);

        $response->assertRedirect();
        $this->assertStringContainsString('membership_id=', $response->headers->get('Location'));
        $this->assertDatabaseHas('advisor_memberships', [
            'advisor_id' => $advisor->id,
            'membership_type_id' => $type->id,
            'amount' => 1200,
        ]);
        $membership = AdvisorMembership::where('advisor_id', $advisor->id)->where('membership_type_id', $type->id)->first();
        $this->assertNotNull($membership);
        $this->assertCount(3, $membership->installments);
        $this->assertSame('PENDIENTE', $membership->installments->first()->status);
    }

    public function test_authenticated_users_can_update_advisor(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::first();
        $currentPinHash = (string) $advisor->pin;
        $this->actingAs($user);

        $response = $this->put(route('inmopro.advisors.update', $advisor), [
            'name' => 'Asesor Actualizado',
            'phone' => $advisor->phone,
            'email' => $advisor->email,
            'username' => 'asesor_actualizado',
            'team_id' => $advisor->team_id,
            'advisor_level_id' => $advisor->advisor_level_id,
            'personal_quota' => 15,
        ]);

        $response->assertRedirect(route('inmopro.advisors.index'));
        $this->assertDatabaseHas('advisors', [
            'id' => $advisor->id,
            'name' => 'Asesor Actualizado',
            'personal_quota' => 15,
            'username' => 'asesor_actualizado',
        ]);
        $this->assertSame($currentPinHash, (string) $advisor->fresh()->pin);
    }

    public function test_advisor_username_must_be_unique_on_store_and_update(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::firstOrFail();
        $otherAdvisor = Advisor::query()->whereKeyNot($advisor->id)->firstOrFail();
        $level = AdvisorLevel::firstOrFail();
        $team = Team::firstOrFail();
        $this->actingAs($user);

        $this->from(route('inmopro.advisors.index'))
            ->post(route('inmopro.advisors.store'), [
                'name' => 'Duplicado',
                'phone' => '999888777',
                'email' => 'duplicado@example.com',
                'username' => $advisor->username,
                'pin' => '123456',
                'team_id' => $team->id,
                'advisor_level_id' => $level->id,
                'personal_quota' => 10,
            ])
            ->assertRedirect(route('inmopro.advisors.index'))
            ->assertSessionHasErrors('username');

        $this->from(route('inmopro.advisors.index'))
            ->put(route('inmopro.advisors.update', $advisor), [
                'name' => $advisor->name,
                'phone' => $advisor->phone,
                'email' => $advisor->email,
                'username' => $otherAdvisor->username,
                'team_id' => $advisor->team_id,
                'advisor_level_id' => $advisor->advisor_level_id,
                'personal_quota' => $advisor->personal_quota,
            ])
            ->assertRedirect(route('inmopro.advisors.index'))
            ->assertSessionHasErrors('username');
    }

    public function test_authenticated_users_can_reset_advisor_pin(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::firstOrFail();
        $advisor->update(['pin' => '654321']);
        $oldPinHash = (string) $advisor->fresh()->pin;
        $this->actingAs($user);

        $response = $this->post(route('inmopro.advisors.reset-pin', $advisor));

        $response->assertRedirect(route('inmopro.advisors.index'));
        $advisor->refresh();
        $this->assertNotSame($oldPinHash, (string) $advisor->pin);
        $this->assertTrue(Hash::check('123456', (string) $advisor->pin));
    }
}
