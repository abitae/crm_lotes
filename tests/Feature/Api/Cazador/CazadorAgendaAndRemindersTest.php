<?php

namespace Tests\Feature\Api\Cazador;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorAgendaEvent;
use App\Models\Inmopro\AdvisorReminder;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\ClientType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CazadorAgendaAndRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Inmopro\TeamSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientTypeSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CitySeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorLevelSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientSeeder::class);
    }

    public function test_advisor_can_create_agenda_event_for_own_client(): void
    {
        $advisor = Advisor::firstOrFail();
        $client = $this->createClientForAdvisor($advisor);

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->postJson(route('api.v1.cazador.agenda-events.store'), [
                'client_id' => $client->id,
                'title' => 'Visita a cliente',
                'notes' => 'Llevar cotización',
                'starts_at' => '2026-03-20T10:00:00',
                'ends_at' => '2026-03-20T11:00:00',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Visita a cliente');

        $this->assertDatabaseHas('advisor_agenda_events', [
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'title' => 'Visita a cliente',
        ]);
    }

    public function test_advisor_cannot_create_agenda_event_for_client_of_another_advisor(): void
    {
        $advisor = Advisor::firstOrFail();
        $otherAdvisor = Advisor::skip(1)->firstOrFail();
        $foreignClient = $this->createClientForAdvisor($otherAdvisor);

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->postJson(route('api.v1.cazador.agenda-events.store'), [
                'client_id' => $foreignClient->id,
                'title' => 'Evento',
                'starts_at' => '2026-03-20T10:00:00',
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'El cliente no pertenece al vendedor autenticado.']);
    }

    public function test_advisor_can_list_own_agenda_events(): void
    {
        $advisor = Advisor::firstOrFail();
        $client = $this->createClientForAdvisor($advisor);
        AdvisorAgendaEvent::create([
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'title' => 'Evento propio',
            'starts_at' => '2026-03-20 10:00:00',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->getJson(route('api.v1.cazador.agenda-events.index'))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.title', 'Evento propio');
    }

    public function test_advisor_cannot_see_another_advisor_agenda_event(): void
    {
        $advisor1 = Advisor::firstOrFail();
        $advisor2 = Advisor::skip(1)->firstOrFail();
        $client2 = $this->createClientForAdvisor($advisor2);
        $event = AdvisorAgendaEvent::create([
            'advisor_id' => $advisor2->id,
            'client_id' => $client2->id,
            'title' => 'Evento de otro',
            'starts_at' => '2026-03-20 10:00:00',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor1))
            ->getJson(route('api.v1.cazador.agenda-events.show', $event))
            ->assertNotFound();
    }

    public function test_advisor_can_create_reminder_for_own_client(): void
    {
        $advisor = Advisor::firstOrFail();
        $client = $this->createClientForAdvisor($advisor);

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->postJson(route('api.v1.cazador.reminders.store'), [
                'client_id' => $client->id,
                'title' => 'Llamar al cliente',
                'remind_at' => '2026-03-20T09:00:00',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Llamar al cliente');

        $this->assertDatabaseHas('advisor_reminders', [
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'title' => 'Llamar al cliente',
        ]);
    }

    public function test_advisor_can_complete_reminder(): void
    {
        $advisor = Advisor::firstOrFail();
        $client = $this->createClientForAdvisor($advisor);
        $reminder = AdvisorReminder::create([
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'title' => 'Recordatorio',
            'remind_at' => now()->subDay(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->postJson(route('api.v1.cazador.reminders.complete', $reminder))
            ->assertOk();

        $this->assertNotNull($reminder->fresh()->completed_at);
    }

    public function test_advisor_cannot_complete_another_advisor_reminder(): void
    {
        $advisor1 = Advisor::firstOrFail();
        $advisor2 = Advisor::skip(1)->firstOrFail();
        $client2 = $this->createClientForAdvisor($advisor2);
        $reminder = AdvisorReminder::create([
            'advisor_id' => $advisor2->id,
            'client_id' => $client2->id,
            'title' => 'Recordatorio de otro',
            'remind_at' => now(),
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor1))
            ->postJson(route('api.v1.cazador.reminders.complete', $reminder))
            ->assertNotFound();
    }

    private function loginToken(Advisor $advisor): string
    {
        return $this->postJson(route('api.v1.cazador.auth.login'), [
            'username' => $advisor->username,
            'pin' => '123456',
        ])->json('token');
    }

    private function createClientForAdvisor(Advisor $advisor): Client
    {
        $type = ClientType::firstOrFail();
        $city = City::firstOrFail();

        return Client::create([
            'name' => 'Cliente test',
            'dni' => (string) (80000000 + $advisor->id),
            'phone' => '999999999',
            'email' => 'test'.$advisor->id.'@test.com',
            'client_type_id' => $type->id,
            'city_id' => $city->id,
            'advisor_id' => $advisor->id,
        ]);
    }
}
