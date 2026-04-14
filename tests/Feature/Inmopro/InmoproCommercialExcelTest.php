<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorLevel;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Team;
use App\Models\User;
use Database\Seeders\Inmopro\AdvisorLevelSeeder;
use Database\Seeders\Inmopro\AdvisorSeeder;
use Database\Seeders\Inmopro\CitySeeder;
use Database\Seeders\Inmopro\CommissionStatusSeeder;
use Database\Seeders\Inmopro\LotStatusSeeder;
use Database\Seeders\Inmopro\ProjectSeeder;
use Database\Seeders\Inmopro\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class InmoproCommercialExcelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(TeamSeeder::class);
        $this->seed(AdvisorLevelSeeder::class);
        $this->seed(LotStatusSeeder::class);
        $this->seed(CommissionStatusSeeder::class);
        $this->seed(ProjectSeeder::class);
        $this->seed(CitySeeder::class);
        $this->seed(AdvisorSeeder::class);
    }

    public function test_guests_cannot_export_advisor_levels_excel(): void
    {
        $this->get(route('inmopro.advisor-levels.export-excel'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_users_can_export_advisor_levels_excel(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('inmopro.advisor-levels.export-excel'))
            ->assertOk()
            ->assertDownload('niveles_asesor.xlsx');
    }

    public function test_authenticated_users_can_download_advisor_levels_template(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('inmopro.advisor-levels.excel-template'))
            ->assertOk()
            ->assertDownload('plantilla_niveles_asesor.xlsx');
    }

    public function test_authenticated_users_can_import_advisor_levels_from_excel(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $file = $this->makeExcelFile([
            ['Nombre', 'Codigo', 'Directa %', 'Piramidal %', 'Color', 'Orden'],
            ['Nivel Import', 'NIVEL_IMP', 8.5, 2, '#112233', 99],
        ]);

        $this->post(route('inmopro.advisor-levels.import-from-excel'), [
            'file' => $file,
        ])->assertRedirect(route('inmopro.advisor-levels.index'));

        $this->assertDatabaseHas('advisor_levels', [
            'code' => 'NIVEL_IMP',
            'name' => 'Nivel Import',
        ]);
    }

    public function test_authenticated_users_can_export_teams_excel(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('inmopro.teams.export-excel'))
            ->assertOk()
            ->assertDownload('teams_comerciales.xlsx');
    }

    public function test_authenticated_users_can_import_teams_from_excel(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $file = $this->makeExcelFile([
            ['Nombre', 'Codigo', 'Descripcion', 'Color', 'Orden', 'Activo', 'Meta grupal'],
            ['Team Import', 'TEAM_IMP', 'Desc', '#aabbcc', 10, 'Si', 50000],
        ]);

        $this->post(route('inmopro.teams.import-from-excel'), [
            'file' => $file,
        ])->assertRedirect(route('inmopro.teams.index'));

        $this->assertDatabaseHas('teams', [
            'code' => 'TEAM_IMP',
            'name' => 'Team Import',
        ]);
    }

    public function test_authenticated_users_can_export_advisors_excel(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('inmopro.advisors.export-excel'))
            ->assertOk()
            ->assertDownload('vendedores.xlsx');
    }

    public function test_authenticated_users_can_import_advisors_from_excel(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $city = City::query()->where('name', 'Lima')->firstOrFail();
        $team = Team::query()->where('code', 'TEAM_NORTE')->firstOrFail();
        $level = AdvisorLevel::query()->where('code', 'NIVEL_1')->firstOrFail();

        $file = $this->makeExcelFile([
            [
                'DNI', 'Nombres', 'Apellidos', 'Fecha nacimiento', 'Telefono', 'Email', 'Ciudad', 'Departamento',
                'Codigo equipo', 'Codigo nivel', 'Cuota personal', 'Activo', 'Username', 'Email superior',
                'Banco', 'Cuenta', 'CCI',
            ],
            [
                '87654321',
                'Vendedor',
                'Import',
                '',
                '999888777',
                'vendedor_import@test.com',
                $city->name,
                $city->department,
                $team->code,
                $level->code,
                125000,
                'Si',
                'vend_import',
                '',
                '',
                '',
                '',
            ],
        ]);

        $preview = $this->withHeader('Accept', 'application/json')
            ->post(route('inmopro.advisors.import-preview'), ['file' => $file]);

        $preview->assertOk();
        $preview->assertJsonPath('can_confirm', true);
        $token = $preview->json('token');
        $this->assertIsString($token);

        $this->post(route('inmopro.advisors.import-confirm'), [
            'token' => $token,
        ])->assertRedirect(route('inmopro.advisors.index'));

        $this->assertDatabaseHas('advisors', [
            'email' => 'vendedor_import@test.com',
            'dni' => '87654321',
            'first_name' => 'Vendedor',
            'last_name' => 'Import',
            'name' => 'Vendedor Import',
            'team_id' => $team->id,
            'advisor_level_id' => $level->id,
            'city_id' => $city->id,
        ]);
    }

    public function test_advisors_import_sets_superior_in_second_pass(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $city = City::query()->where('name', 'Lima')->firstOrFail();
        $team = Team::query()->where('code', 'TEAM_NORTE')->firstOrFail();
        $level = AdvisorLevel::query()->where('code', 'NIVEL_1')->firstOrFail();

        $boss = Advisor::query()->where('email', 'director1@inmopro.com')->firstOrFail();

        $file = $this->makeExcelFile([
            [
                'DNI', 'Nombres', 'Apellidos', 'Fecha nacimiento', 'Telefono', 'Email', 'Ciudad', 'Departamento',
                'Codigo equipo', 'Codigo nivel', 'Cuota personal', 'Activo', 'Username', 'Email superior',
                'Banco', 'Cuenta', 'CCI',
            ],
            [
                '87654322',
                'Subordinado',
                'Test',
                '',
                '911222333',
                'sub_test_jerarquia@example.com',
                $city->name,
                $city->department,
                $team->code,
                $level->code,
                50000,
                'Si',
                'sub_jer',
                $boss->email,
                '',
                '',
                '',
            ],
        ]);

        $preview = $this->withHeader('Accept', 'application/json')
            ->post(route('inmopro.advisors.import-preview'), ['file' => $file]);

        $preview->assertOk();
        $token = $preview->json('token');
        $this->assertIsString($token);

        $this->post(route('inmopro.advisors.import-confirm'), [
            'token' => $token,
        ])->assertRedirect(route('inmopro.advisors.index'));

        $this->assertDatabaseHas('advisors', [
            'email' => 'sub_test_jerarquia@example.com',
            'dni' => '87654322',
            'superior_id' => $boss->id,
        ]);
    }

    /**
     * @param  array<int, array<int, int|float|string>>  $rows
     */
    private function makeExcelFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValueByColumnAndRow($columnIndex + 1, $rowIndex + 1, $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'inmopro_excel_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return new UploadedFile(
            $path,
            'datos.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}
