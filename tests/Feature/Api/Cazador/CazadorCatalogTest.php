<?php

namespace Tests\Feature\Api\Cazador;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\Project;
use Database\Seeders\Inmopro\AdvisorLevelSeeder;
use Database\Seeders\Inmopro\AdvisorSeeder;
use Database\Seeders\Inmopro\CitySeeder;
use Database\Seeders\Inmopro\ClientSeeder;
use Database\Seeders\Inmopro\ClientTypeSeeder;
use Database\Seeders\Inmopro\CommissionStatusSeeder;
use Database\Seeders\Inmopro\LotSeeder;
use Database\Seeders\Inmopro\LotStatusSeeder;
use Database\Seeders\Inmopro\ProjectSeeder;
use Database\Seeders\Inmopro\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CazadorCatalogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TeamSeeder::class);
        $this->seed(ClientTypeSeeder::class);
        $this->seed(AdvisorLevelSeeder::class);
        $this->seed(LotStatusSeeder::class);
        $this->seed(CommissionStatusSeeder::class);
        $this->seed(ProjectSeeder::class);
        $this->seed(CitySeeder::class);
        $this->seed(AdvisorSeeder::class);
        $this->seed(ClientSeeder::class);
        $this->seed(LotSeeder::class);
    }

    public function test_projects_index_requires_authentication(): void
    {
        $this->getJson(route('api.v1.cazador.projects.index'))
            ->assertUnauthorized();
    }

    public function test_advisor_can_list_projects(): void
    {
        $advisor = Advisor::firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->getJson(route('api.v1.cazador.projects.index'))
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'name', 'location', 'total_lots', 'lots_count'],
                ],
            ]);

        $this->assertGreaterThan(0, Project::query()->count());
    }

    public function test_advisor_can_show_project(): void
    {
        $advisor = Advisor::firstOrFail();
        $project = Project::query()->firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->getJson(route('api.v1.cazador.projects.show', $project))
            ->assertOk()
            ->assertJsonPath('data.id', $project->id)
            ->assertJsonStructure([
                'data' => ['id', 'name', 'location', 'total_lots', 'lots_count', 'blocks'],
            ]);
    }

    public function test_advisor_can_list_lots_available_by_default(): void
    {
        $advisor = Advisor::firstOrFail();

        $response = $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->getJson(route('api.v1.cazador.lots.index'))
            ->assertOk();

        $rows = $response->json('data');
        $this->assertIsArray($rows);

        foreach ($rows as $row) {
            $this->assertSame('LIBRE', $row['status']['code'] ?? null);
        }
    }

    public function test_advisor_can_list_lots_including_non_available_when_disabled(): void
    {
        $advisor = Advisor::firstOrFail();
        $total = Lot::query()->count();
        $this->assertGreaterThan(0, $total);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->getJson(route('api.v1.cazador.lots.index', ['available_only' => false]))
            ->assertOk();

        $this->assertCount($total, $response->json('data'));
    }

    public function test_advisor_can_show_lot_detail(): void
    {
        $advisor = Advisor::firstOrFail();
        $lot = Lot::query()->firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->getJson(route('api.v1.cazador.lots.show', $lot))
            ->assertOk()
            ->assertJsonPath('data.id', $lot->id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'block',
                    'number',
                    'project',
                    'status',
                    'can_pre_reserve',
                    'pre_reservations',
                ],
            ]);
    }

    private function loginToken(Advisor $advisor): string
    {
        return $this->postJson(route('api.v1.cazador.auth.login'), [
            'username' => $advisor->username,
            'pin' => '123456',
        ])->assertOk()->json('token');
    }
}
