<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\ClientType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class InmoproClientsTest extends TestCase
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
    }

    public function test_guests_cannot_visit_clients_index(): void
    {
        $response = $this->get(route('inmopro.clients.index'));
        $response->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_visit_clients_index(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('inmopro.clients.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('inmopro/clients/index')->has('clients'));
    }

    public function test_authenticated_users_can_create_client(): void
    {
        $user = User::factory()->create();
        $type = ClientType::first();
        $advisor = Advisor::first();
        $city = City::first();
        $this->actingAs($user);

        $response = $this->post(route('inmopro.clients.store'), [
            'name' => 'Nuevo Cliente Test',
            'dni' => '12345678',
            'phone' => '999888777',
            'email' => 'test@example.com',
            'client_type_id' => $type->id,
            'advisor_id' => $advisor->id,
            'city_id' => $city?->id,
        ]);

        $response->assertRedirect(route('inmopro.clients.index'));
        $this->assertDatabaseHas('clients', [
            'name' => 'Nuevo Cliente Test',
            'dni' => '12345678',
            'client_type_id' => $type->id,
            'advisor_id' => $advisor->id,
        ]);
    }

    public function test_authenticated_users_can_update_client(): void
    {
        $user = User::factory()->create();
        $client = Client::first();
        $this->actingAs($user);

        $response = $this->put(route('inmopro.clients.update', $client), [
            'name' => 'Cliente Actualizado',
            'dni' => $client->dni,
            'phone' => $client->phone,
            'email' => $client->email,
            'client_type_id' => $client->client_type_id,
            'advisor_id' => $client->advisor_id,
            'city_id' => $client->city_id,
        ]);

        $response->assertRedirect(route('inmopro.clients.index'));
        $this->assertDatabaseHas('clients', [
            'id' => $client->id,
            'name' => 'Cliente Actualizado',
        ]);
    }

    public function test_clients_search_returns_json_with_like_match(): void
    {
        $user = User::factory()->create();
        $typeId = ClientType::first()->id;
        $advisorId = Advisor::first()->id;
        $this->actingAs($user);

        Client::create(['name' => 'Juan Perez', 'dni' => '11111111', 'phone' => '', 'client_type_id' => $typeId, 'advisor_id' => $advisorId]);
        Client::create(['name' => 'Maria Garcia', 'dni' => '22222222', 'phone' => '', 'client_type_id' => $typeId, 'advisor_id' => $advisorId]);

        $response = $this->getJson(route('inmopro.clients.search', ['q' => 'Juan']));
        $response->assertOk();
        $response->assertJsonFragment(['name' => 'Juan Perez']);

        $response2 = $this->getJson(route('inmopro.clients.search', ['q' => '2222']));
        $response2->assertOk();
        $response2->assertJsonFragment(['dni' => '22222222']);
    }

    public function test_clients_index_filters_by_advisor(): void
    {
        $user = User::factory()->create();
        $advisor = Advisor::query()->firstOrFail();
        $otherAdvisor = Advisor::query()->whereKeyNot($advisor->id)->firstOrFail();
        $this->actingAs($user);

        $response = $this->get(route('inmopro.clients.index', ['advisor_id' => $advisor->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('inmopro/clients/index')
            ->where('filters.advisor_id', (string) $advisor->id)
            ->has('clients.data')
        );

        $clientAdvisorIds = collect($response->viewData('page')['props']['clients']['data'])
            ->pluck('advisor.id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->assertNotContains($otherAdvisor->id, $clientAdvisorIds);
    }

    public function test_authenticated_users_can_export_clients_excel(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get(route('inmopro.clients.export-excel'));

        $response->assertOk();
        $response->assertDownload('clientes.xlsx');
    }

    public function test_authenticated_users_can_import_clients_from_excel(): void
    {
        $user = User::factory()->create();
        $type = ClientType::query()->firstOrFail();
        $advisor = Advisor::query()->firstOrFail();
        $city = City::query()->firstOrFail();
        $this->actingAs($user);

        $file = $this->makeClientsExcelFile([
            ['Nombre', 'DNI', 'Telefono', 'Email', 'Referido por', 'Tipo cliente', 'Ciudad', 'Asesor'],
            ['Cliente Excel', '44556677', '987654321', 'excel@test.com', 'Campana digital', $type->name, $city->name, $advisor->name],
        ]);

        $this->post(route('inmopro.clients.import-from-excel'), [
            'file' => $file,
        ])->assertRedirect(route('inmopro.clients.index'));

        $this->assertDatabaseHas('clients', [
            'dni' => '44556677',
            'name' => 'Cliente Excel',
            'phone' => '987654321',
            'advisor_id' => $advisor->id,
            'client_type_id' => $type->id,
            'city_id' => $city->id,
        ]);
    }

    private function makeClientsExcelFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'clients_excel_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return new UploadedFile(
            $path,
            'clientes.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}
