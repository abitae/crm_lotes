<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Commission;
use App\Models\Inmopro\CommissionStatus;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InmoproCommissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Inmopro\AdvisorLevelSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\LotStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CommissionStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ProjectSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\LotSeeder::class);
    }

    public function test_guests_cannot_visit_commissions_index(): void
    {
        $response = $this->get(route('inmopro.commissions.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_commissions_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('inmopro.commissions.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('inmopro/commissions')
            ->has('commissions')
            ->has('commissionStatuses'));
    }

    public function test_authenticated_users_can_mark_commission_as_paid(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $lot = Lot::whereNotNull('advisor_id')->where('lot_status_id', '!=', LotStatus::where('code', 'TRANSFERIDO')->first()?->id)->first();
        if (! $lot) {
            $this->markTestSkipped('No non-transferred lot with advisor in database');
        }
        $transferidoId = LotStatus::where('code', 'TRANSFERIDO')->first()->id;
        $this->patch(route('inmopro.lots.update', $lot), [
            'lot_status_id' => $transferidoId,
            'client_id' => $lot->client_id,
            'advisor_id' => $lot->advisor_id,
        ]);
        $commission = Commission::where('lot_id', $lot->id)->first();
        $this->assertNotNull($commission, 'Commission should be created when lot is transferred');

        $paidStatus = CommissionStatus::where('code', 'PAGADO')->first();
        $response = $this->post(route('inmopro.commissions.mark-as-paid', $commission));

        $response->assertRedirect();
        $commission->refresh();
        $this->assertSame((int) $paidStatus->id, (int) $commission->commission_status_id);
    }
}
