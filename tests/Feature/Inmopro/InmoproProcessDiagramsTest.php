<?php

namespace Tests\Feature\Inmopro;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproProcessDiagramsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_users_can_view_process_diagrams_with_mermaid_blocks(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('inmopro.process-diagrams.index'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('inmopro/process-diagrams')
                ->has('diagrams', 13)
                ->where('diagrams.0.title', '1. Arquitectura general: actores, canales y almacenamiento')
            );
    }
}
