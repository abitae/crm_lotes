<?php

namespace Tests\Feature\Inmopro;

use App\Ai\Agents\LotFollowUpAssistant;
use App\Models\Inmopro\Lot;
use App\Models\User;
use Database\Seeders\Inmopro\AdvisorLevelSeeder;
use Database\Seeders\Inmopro\AdvisorSeeder;
use Database\Seeders\Inmopro\ClientSeeder;
use Database\Seeders\Inmopro\CommissionStatusSeeder;
use Database\Seeders\Inmopro\LotSeeder;
use Database\Seeders\Inmopro\LotStatusSeeder;
use Database\Seeders\Inmopro\ProjectSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproLotAiFollowUpSuggestionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(AdvisorLevelSeeder::class);
        $this->seed(LotStatusSeeder::class);
        $this->seed(CommissionStatusSeeder::class);
        $this->seed(ProjectSeeder::class);
        $this->seed(AdvisorSeeder::class);
        $this->seed(ClientSeeder::class);
        $this->seed(LotSeeder::class);
    }

    public function test_guests_cannot_request_lot_ai_suggestion(): void
    {
        $lot = Lot::firstOrFail();

        $response = $this->postJson(route('inmopro.lots.ai-follow-up-suggestion', $lot));

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_receives_suggestion_when_agent_is_faked(): void
    {
        LotFollowUpAssistant::fake(['Texto de seguimiento sugerido para prueba.']);

        $user = User::factory()->create();
        $lot = Lot::with(['project', 'status', 'client', 'advisor'])->firstOrFail();
        $this->actingAs($user);

        $response = $this->postJson(route('inmopro.lots.ai-follow-up-suggestion', $lot), [
            'extra_context' => 'Cliente pidió llamar la próxima semana.',
        ]);

        $response->assertOk();
        $response->assertJson([
            'suggestion' => 'Texto de seguimiento sugerido para prueba.',
        ]);

        LotFollowUpAssistant::assertPrompted(function ($prompt) use ($lot) {
            return $prompt->contains('Datos del lote:')
                && $prompt->contains((string) ($lot->project?->name ?? ''));
        });
    }

    public function test_extra_context_must_not_exceed_max_length(): void
    {
        LotFollowUpAssistant::fake();

        $user = User::factory()->create();
        $lot = Lot::firstOrFail();
        $this->actingAs($user);

        $response = $this->postJson(route('inmopro.lots.ai-follow-up-suggestion', $lot), [
            'extra_context' => str_repeat('a', 2001),
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['extra_context']);
    }

    public function test_ai_route_is_rate_limited_per_user(): void
    {
        LotFollowUpAssistant::fake(['ok']);

        $user = User::factory()->create();
        $lot = Lot::firstOrFail();
        $this->actingAs($user);

        for ($i = 0; $i < 10; $i++) {
            $this->postJson(route('inmopro.lots.ai-follow-up-suggestion', $lot))->assertOk();
        }

        $this->postJson(route('inmopro.lots.ai-follow-up-suggestion', $lot))
            ->assertStatus(429);
    }
}
