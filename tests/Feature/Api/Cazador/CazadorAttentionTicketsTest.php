<?php

namespace Tests\Feature\Api\Cazador;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AttentionTicket;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\ClientType;
use App\Models\Inmopro\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CazadorAttentionTicketsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Inmopro\TeamSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientTypeSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CitySeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorLevelSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\LotStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CommissionStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ProjectSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientSeeder::class);
    }

    public function test_advisor_can_create_attention_ticket_for_own_client(): void
    {
        $ownType = ClientType::where('code', 'PROPIO')->firstOrFail();
        $client = Client::where('client_type_id', $ownType->id)->firstOrFail();
        $advisor = Advisor::findOrFail($client->advisor_id);
        $project = Project::firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->postJson(route('api.v1.cazador.attention-tickets.store'), [
                'client_id' => $client->id,
                'project_id' => $project->id,
                'notes' => 'Cliente solicita visita.',
            ])
            ->assertCreated()
            ->assertJsonFragment(['status' => 'pendiente']);

        $this->assertDatabaseHas('attention_tickets', [
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'status' => 'pendiente',
        ]);
    }

    public function test_advisor_can_create_attention_ticket_for_owned_datero_client(): void
    {
        $dateroType = ClientType::where('code', 'DATERO')->firstOrFail();
        $client = Client::where('client_type_id', $dateroType->id)->firstOrFail();
        $advisor = Advisor::findOrFail($client->advisor_id);
        $project = Project::firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->postJson(route('api.v1.cazador.attention-tickets.store'), [
                'client_id' => $client->id,
                'project_id' => $project->id,
            ])
            ->assertCreated()
            ->assertJsonFragment(['status' => 'pendiente']);
    }

    public function test_advisor_cannot_create_attention_ticket_for_client_of_another_advisor(): void
    {
        $ownType = ClientType::where('code', 'PROPIO')->firstOrFail();
        $advisor = Advisor::firstOrFail();
        $foreignClient = Client::where('client_type_id', $ownType->id)
            ->where('advisor_id', '!=', $advisor->id)
            ->firstOrFail();
        $project = Project::firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->postJson(route('api.v1.cazador.attention-tickets.store'), [
                'client_id' => $foreignClient->id,
                'project_id' => $project->id,
            ])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'El cliente debe pertenecer al vendedor y ser PROPIO o DATERO.']);
    }

    public function test_advisor_can_cancel_owned_attention_ticket(): void
    {
        $ownType = ClientType::where('code', 'PROPIO')->firstOrFail();
        $client = Client::where('client_type_id', $ownType->id)->firstOrFail();
        $advisor = Advisor::findOrFail($client->advisor_id);
        $project = Project::firstOrFail();
        $ticket = AttentionTicket::create([
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'status' => 'pendiente',
            'notes' => 'Pendiente de revisión',
        ]);

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->postJson(route('api.v1.cazador.attention-tickets.cancel', $ticket), [
                'notes' => 'El cliente pidió cancelar la solicitud.',
            ])
            ->assertOk()
            ->assertJsonFragment(['status' => 'cancelado']);

        $this->assertDatabaseHas('attention_tickets', [
            'id' => $ticket->id,
            'status' => 'cancelado',
        ]);
    }

    private function loginToken(Advisor $advisor): string
    {
        return $this->postJson(route('api.v1.cazador.auth.login'), [
            'username' => $advisor->username,
            'pin' => '123456',
        ])->json('token');
    }
}
