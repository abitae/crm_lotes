<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AttentionTicket;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproAttentionTicketsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(\Database\Seeders\Inmopro\TeamSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientTypeSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CitySeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorLevelSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\LotStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CommissionStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ProjectSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\LotSeeder::class);
    }

    public function test_guests_cannot_visit_attention_tickets_index(): void
    {
        $response = $this->get(route('inmopro.attention-tickets.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_attention_tickets_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('inmopro.attention-tickets.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('inmopro/operations/attention-tickets/index')->has('tickets'));
    }

    public function test_authenticated_users_can_create_attention_ticket_in_pending_status(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->whereNotNull('advisor_id')->firstOrFail();
        $advisor = Advisor::findOrFail($client->advisor_id);
        $project = Project::firstOrFail();
        $this->actingAs($user);

        $response = $this->post(route('inmopro.attention-tickets.store'), [
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'notes' => 'Solicitud desde operaciones',
        ]);

        $response->assertRedirect(route('inmopro.attention-tickets.index'));
        $this->assertDatabaseHas('attention_tickets', [
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'status' => 'pendiente',
        ]);
    }

    public function test_authenticated_users_can_schedule_attention_ticket_from_admin(): void
    {
        $user = User::factory()->create();
        $client = Client::query()->whereNotNull('advisor_id')->firstOrFail();
        $advisor = Advisor::findOrFail($client->advisor_id);
        $project = Project::firstOrFail();
        $this->actingAs($user);

        $ticket = AttentionTicket::create([
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
            'project_id' => $project->id,
            'status' => 'pendiente',
            'notes' => 'Pendiente de agenda',
        ]);

        $response = $this->put(route('inmopro.attention-tickets.update', $ticket), [
            'status' => 'agendado',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'notes' => 'Visita confirmada',
        ]);

        $response->assertRedirect(route('inmopro.attention-tickets.show', $ticket));
        $this->assertDatabaseHas('attention_tickets', [
            'id' => $ticket->id,
            'status' => 'agendado',
            'notes' => 'Visita confirmada',
        ]);
    }

    public function test_authenticated_users_can_view_legacy_attention_ticket_and_delivery_deed(): void
    {
        $user = User::factory()->create();
        $lot = Lot::whereNotNull('client_id')->firstOrFail();
        $advisor = Advisor::findOrFail($lot->advisor_id);
        $this->actingAs($user);

        $ticket = AttentionTicket::create([
            'advisor_id' => $advisor->id,
            'client_id' => $lot->client_id,
            'project_id' => $lot->project_id,
            'lot_id' => $lot->id,
            'scheduled_at' => now()->addDay(),
            'status' => 'agendado',
        ]);

        $response = $this->get(route('inmopro.attention-tickets.show', $ticket));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('inmopro/operations/attention-tickets/show')->has('ticket'));

        $responseDeed = $this->get(route('inmopro.attention-tickets.delivery-deed', $ticket));
        $responseDeed->assertOk();
        $responseDeed->assertInertia(fn ($page) => $page->component('inmopro/operations/delivery-deed-print')->has('companyName'));
    }
}
