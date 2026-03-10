<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AttentionTicket;
use App\Models\Inmopro\Lot;
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

    public function test_authenticated_users_can_create_attention_ticket(): void
    {
        $user = User::factory()->create();
        $lot = Lot::whereNotNull('client_id')->first();
        $advisor = Advisor::first();
        $this->actingAs($user);

        $this->assertNotNull($lot, 'Need a lot with client');
        $this->assertNotNull($advisor, 'Need an advisor');

        $response = $this->post(route('inmopro.attention-tickets.store'), [
            'advisor_id' => $advisor->id,
            'lot_id' => $lot->id,
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'notes' => 'Entrega programada',
        ]);

        $response->assertRedirect(route('inmopro.attention-tickets.index'));
        $this->assertDatabaseHas('attention_tickets', [
            'lot_id' => $lot->id,
            'advisor_id' => $advisor->id,
            'status' => 'agendado',
        ]);
    }

    public function test_authenticated_users_can_view_attention_ticket_and_delivery_deed(): void
    {
        $user = User::factory()->create();
        $lot = Lot::whereNotNull('client_id')->first();
        $advisor = Advisor::first();
        $this->actingAs($user);

        $ticket = AttentionTicket::create([
            'advisor_id' => $advisor->id,
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
