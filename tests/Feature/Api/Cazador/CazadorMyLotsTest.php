<?php

namespace Tests\Feature\Api\Cazador;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
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

class CazadorMyLotsTest extends TestCase
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

    public function test_my_lots_requires_authentication(): void
    {
        $this->getJson(route('api.v1.cazador.my-lots.index'))
            ->assertUnauthorized();
    }

    public function test_advisor_sees_only_own_lots(): void
    {
        $advisor = $this->advisorWithAtLeastOneLot();
        $expected = Lot::query()->where('advisor_id', $advisor->id)->count();
        $this->assertGreaterThan(0, $expected);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->getJson(route('api.v1.cazador.my-lots.index'))
            ->assertOk();

        $this->assertCount($expected, $response->json('data'));
    }

    public function test_advisor_can_filter_my_lots_by_status(): void
    {
        $advisor = $this->advisorWithAtLeastOneLot();
        $reservedId = LotStatus::where('code', 'RESERVADO')->value('id');
        $this->assertNotNull($reservedId);

        Lot::query()->where('advisor_id', $advisor->id)->update(['lot_status_id' => $reservedId]);

        $response = $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->getJson(route('api.v1.cazador.my-lots.index', ['status' => 'RESERVADO']))
            ->assertOk();

        $rows = $response->json('data');
        $this->assertNotEmpty($rows);
        foreach ($rows as $row) {
            $this->assertSame('RESERVADO', $row['status']['code'] ?? null);
        }
    }

    public function test_invalid_status_returns_validation_error(): void
    {
        $advisor = Advisor::firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->getJson(route('api.v1.cazador.my-lots.index', ['status' => 'NO_EXISTE']))
            ->assertUnprocessable();
    }

    private function loginToken(Advisor $advisor): string
    {
        return $this->postJson(route('api.v1.cazador.auth.login'), [
            'username' => $advisor->username,
            'pin' => '123456',
        ])->assertOk()->json('token');
    }

    private function advisorWithAtLeastOneLot(): Advisor
    {
        $advisorId = Lot::query()->whereNotNull('advisor_id')->value('advisor_id');
        $this->assertNotNull($advisorId);

        return Advisor::query()->findOrFail($advisorId);
    }
}
