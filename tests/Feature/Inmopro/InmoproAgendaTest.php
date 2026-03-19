<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorAgendaEvent;
use App\Models\Inmopro\AdvisorReminder;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\ClientType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproAgendaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(\Database\Seeders\Inmopro\AdvisorLevelSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\TeamSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientTypeSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CitySeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientSeeder::class);
    }

    public function test_guests_cannot_visit_agenda(): void
    {
        $this->get(route('inmopro.agenda.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_agenda(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get(route('inmopro.agenda.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('inmopro/agenda/index')
                ->has('advisors')
                ->has('events')
                ->has('remindersPending')
                ->has('filters'));
    }

    public function test_authenticated_users_can_visit_agenda_with_advisor_filter(): void
    {
        $advisor = Advisor::firstOrFail();
        $this->actingAs(User::factory()->create());

        $this->get(route('inmopro.agenda.index', ['advisor_id' => $advisor->id]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('inmopro/agenda/index')
                ->has('clients')
                ->where('filters.advisor_id', (string) $advisor->id));
    }

    public function test_authenticated_users_can_create_agenda_event(): void
    {
        $advisor = Advisor::firstOrFail();
        $client = $this->createClientForAdvisor($advisor);
        $this->actingAs(User::factory()->create());

        $this->post(route('inmopro.advisor-agenda-events.store'), [
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'title' => 'Llamada de seguimiento',
            'notes' => 'Oferta promocional',
            'starts_at' => '2026-03-20 10:00:00',
            'ends_at' => '2026-03-20 11:00:00',
        ])->assertRedirect(route('inmopro.agenda.index', ['advisor_id' => $advisor->id]));

        $this->assertDatabaseHas('advisor_agenda_events', [
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'title' => 'Llamada de seguimiento',
        ]);
    }

    public function test_authenticated_users_can_create_reminder(): void
    {
        $client = Client::firstOrFail();
        $advisor = Advisor::findOrFail($client->advisor_id);
        $this->actingAs(User::factory()->create());

        $this->post(route('inmopro.advisor-reminders.store'), [
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'title' => 'Llamar al cliente',
            'notes' => 'Recordar oferta',
            'remind_at' => '2026-03-20 09:00:00',
        ])->assertRedirect(route('inmopro.agenda.index', ['advisor_id' => $advisor->id]));

        $this->assertDatabaseHas('advisor_reminders', [
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'title' => 'Llamar al cliente',
        ]);
    }

    public function test_authenticated_users_can_complete_reminder(): void
    {
        $advisor = Advisor::firstOrFail();
        $client = $this->createClientForAdvisor($advisor);
        $reminder = AdvisorReminder::create([
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'title' => 'Recordatorio',
            'remind_at' => now()->subDay(),
        ]);
        $this->actingAs(User::factory()->create());

        $this->post(route('inmopro.advisor-reminders.complete', $reminder))
            ->assertRedirect(route('inmopro.agenda.index', ['advisor_id' => $advisor->id]));

        $this->assertNotNull($reminder->fresh()->completed_at);
    }

    public function test_authenticated_users_can_update_agenda_event(): void
    {
        $advisor = Advisor::firstOrFail();
        $client = $this->createClientForAdvisor($advisor);
        $event = AdvisorAgendaEvent::create([
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'title' => 'Original',
            'starts_at' => '2026-03-20 10:00:00',
            'ends_at' => '2026-03-20 11:00:00',
        ]);
        $this->actingAs(User::factory()->create());

        $this->put(route('inmopro.advisor-agenda-events.update', $event), [
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'title' => 'Actualizado',
            'notes' => 'Notas',
            'starts_at' => '2026-03-20 10:00:00',
            'ends_at' => '2026-03-20 12:00:00',
        ])->assertRedirect();

        $this->assertDatabaseHas('advisor_agenda_events', [
            'id' => $event->id,
            'title' => 'Actualizado',
        ]);
    }

    public function test_authenticated_users_can_delete_agenda_event(): void
    {
        $advisor = Advisor::firstOrFail();
        $client = $this->createClientForAdvisor($advisor);
        $event = AdvisorAgendaEvent::create([
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'title' => 'A eliminar',
            'starts_at' => '2026-03-20 10:00:00',
        ]);
        $this->actingAs(User::factory()->create());

        $this->delete(route('inmopro.advisor-agenda-events.destroy', $event))
            ->assertRedirect();

        $this->assertDatabaseMissing('advisor_agenda_events', ['id' => $event->id]);
    }

    private function createClientForAdvisor(Advisor $advisor): Client
    {
        $type = ClientType::firstOrFail();
        $city = City::firstOrFail();

        return Client::create([
            'name' => 'Cliente test',
            'dni' => '99999999',
            'phone' => '999999999',
            'email' => 'test@test.com',
            'client_type_id' => $type->id,
            'city_id' => $city->id,
            'advisor_id' => $advisor->id,
        ]);
    }
}
