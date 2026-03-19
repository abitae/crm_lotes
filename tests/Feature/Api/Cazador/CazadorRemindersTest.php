<?php

namespace Tests\Feature\Api\Cazador;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorReminder;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\ClientType;
use Database\Seeders\Inmopro\AdvisorLevelSeeder;
use Database\Seeders\Inmopro\AdvisorSeeder;
use Database\Seeders\Inmopro\CitySeeder;
use Database\Seeders\Inmopro\ClientSeeder;
use Database\Seeders\Inmopro\ClientTypeSeeder;
use Database\Seeders\Inmopro\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CazadorRemindersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TeamSeeder::class);
        $this->seed(ClientTypeSeeder::class);
        $this->seed(CitySeeder::class);
        $this->seed(AdvisorLevelSeeder::class);
        $this->seed(AdvisorSeeder::class);
        $this->seed(ClientSeeder::class);
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

    public function test_advisor_cannot_create_reminder_for_non_propio_client(): void
    {
        $advisor = Advisor::firstOrFail();
        $city = City::firstOrFail();
        $prospectoType = ClientType::query()->where('code', 'PROSPECTO')->firstOrFail();
        $client = Client::create([
            'name' => 'Cliente prospecto',
            'dni' => (string) (81000000 + $advisor->id),
            'phone' => '988888888',
            'email' => 'prospecto'.$advisor->id.'@test.com',
            'client_type_id' => $prospectoType->id,
            'city_id' => $city->id,
            'advisor_id' => $advisor->id,
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->postJson(route('api.v1.cazador.reminders.store'), [
                'client_id' => $client->id,
                'title' => 'Llamar',
                'remind_at' => '2026-03-20T09:00:00',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'El cliente debe pertenecer al vendedor y ser de tipo PROPIO.');
    }

    private function createClientForAdvisor(Advisor $advisor): Client
    {
        $type = ClientType::query()->where('code', 'PROPIO')->firstOrFail();
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
