<?php

namespace Tests\Feature\Inmopro;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproReportsPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(\Database\Seeders\Inmopro\TeamSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorLevelSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ProjectSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\LotStatusSeeder::class);
    }

    public function test_guests_cannot_access_reports_pdf(): void
    {
        $response = $this->get(route('inmopro.reports.pdf'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_receive_report_pdf(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        foreach (['projects', 'teams', 'advisors'] as $view) {
            $response = $this->get(route('inmopro.reports.pdf', ['view' => $view]));
            $response->assertOk();
            $response->assertHeader('Content-Type', 'application/pdf');
        }
    }
}
