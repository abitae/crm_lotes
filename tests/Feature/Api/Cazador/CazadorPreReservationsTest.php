<?php

namespace Tests\Feature\Api\Cazador;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\ClientType;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CazadorPreReservationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\Inmopro\TeamSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientTypeSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorLevelSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\LotStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CommissionStatusSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ProjectSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\CitySeeder::class);
        $this->seed(\Database\Seeders\Inmopro\AdvisorSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\ClientSeeder::class);
        $this->seed(\Database\Seeders\Inmopro\LotSeeder::class);
    }

    public function test_advisor_can_create_pre_reservation_for_available_lot(): void
    {
        Storage::fake('public');

        $ownType = ClientType::where('code', 'PROPIO')->firstOrFail();
        $client = Client::where('client_type_id', $ownType->id)->firstOrFail();
        $advisor = Advisor::findOrFail($client->advisor_id);
        $lot = Lot::whereHas('status', fn ($query) => $query->where('code', 'LIBRE'))->firstOrFail();
        $preReservationStatus = LotStatus::where('code', 'PRERESERVA')->firstOrFail();

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->withHeader('Accept', 'application/json')
            ->post(route('api.v1.cazador.lots.pre-reservations.store', $lot), [
                'client_id' => $client->id,
                'project_id' => $lot->project_id,
                'lot_id' => $lot->id,
                'amount' => 1500,
                'voucher_image' => UploadedFile::fake()->image('voucher.png'),
                'payment_reference' => 'OP-123',
                'notes' => 'Abono inicial',
            ])->assertCreated()
            ->assertJsonFragment(['status' => 'PENDIENTE'])
            ->assertJsonFragment(['amount' => '1500.00']);

        $this->assertDatabaseHas('lot_pre_reservations', [
            'lot_id' => $lot->id,
            'client_id' => $client->id,
            'advisor_id' => $advisor->id,
            'status' => 'PENDIENTE',
            'amount' => 1500,
        ]);
        $this->assertDatabaseHas('lots', [
            'id' => $lot->id,
            'lot_status_id' => $preReservationStatus->id,
            'advisor_id' => $advisor->id,
            'client_id' => $client->id,
        ]);
    }

    public function test_advisor_cannot_create_pre_reservation_when_lot_in_body_does_not_match_route(): void
    {
        Storage::fake('public');

        $ownType = ClientType::where('code', 'PROPIO')->firstOrFail();
        $client = Client::where('client_type_id', $ownType->id)->firstOrFail();
        $advisor = Advisor::findOrFail($client->advisor_id);
        $lots = Lot::whereHas('status', fn ($query) => $query->where('code', 'LIBRE'))->take(2)->get();
        $routeLot = $lots->first();
        $payloadLot = $lots->last();

        $this->assertNotNull($routeLot);
        $this->assertNotNull($payloadLot);

        $this->withHeader('Authorization', 'Bearer '.$this->loginToken($advisor))
            ->withHeader('Accept', 'application/json')
            ->post(route('api.v1.cazador.lots.pre-reservations.store', $routeLot), [
                'client_id' => $client->id,
                'project_id' => $routeLot->project_id,
                'lot_id' => $payloadLot->id,
                'amount' => 1500,
                'voucher_image' => UploadedFile::fake()->image('voucher.png'),
            ])->assertStatus(422)
            ->assertJsonFragment(['message' => 'El lote enviado no coincide con la ruta.']);
    }

    private function loginToken(Advisor $advisor): string
    {
        return $this->postJson(route('api.v1.cazador.auth.login'), [
            'username' => $advisor->username,
            'pin' => '123456',
        ])->json('token');
    }
}
