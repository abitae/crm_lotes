<?php

namespace Tests\Feature\Inmopro;

use App\Models\Inmopro\Client;
use App\Models\Inmopro\Project;
use App\Models\Inmopro\ProjectType;
use App\Models\User;
use Database\Seeders\Inmopro\AdvisorLevelSeeder;
use Database\Seeders\Inmopro\AdvisorSeeder;
use Database\Seeders\Inmopro\ClientTypeSeeder;
use Database\Seeders\Inmopro\CommissionStatusSeeder;
use Database\Seeders\Inmopro\LotStatusSeeder;
use Database\Seeders\Inmopro\ProjectTypeSeeder;
use Database\Seeders\Inmopro\TeamSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class InmoproProjectsExcelTest extends TestCase
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
        $this->seed(ProjectTypeSeeder::class);
        $this->seed(AdvisorSeeder::class);
    }

    public function test_authenticated_users_can_download_projects_excel_template(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get(route('inmopro.projects.excel-template'))
            ->assertOk()
            ->assertDownload('plantilla_proyecto_lotes.xlsx');
    }

    public function test_projects_import_preview_reads_proyecto_and_telefono_columns(): void
    {
        $user = User::factory()->create();
        $projectType = ProjectType::query()->firstOrFail();
        $this->actingAs($user);

        $file = $this->makeProjectsExcelFile([
            [
                'PROYECTO',
                'ITEM',
                'NOMBRE CLIENTE',
                'TELEFONO',
                'MZ',
                'LOTE',
                'AREA',
                'MONTO',
                'ADELANTO - SEPARACION',
                'MONTO RESTANTE',
                'FACTURACIÓN',
                'DNI CLIENTE',
                'FECHA LIMITE DE PAGO',
                'ESTADO DE LOTE',
                'N° DE OPERACIÓN S.',
                'FECHA DE CONTRATO',
                'NRO DE CONTRATO',
            ],
            [
                'Proyecto Excel Test',
                1,
                'Juan Perez',
                '999888777',
                'A',
                1,
                100,
                25000,
                '',
                25000,
                '',
                '11223344',
                '',
                'LIBRE',
                '',
                '',
                '',
            ],
        ]);

        $previewResponse = $this->post(route('inmopro.projects.import-preview'), [
            'file' => $file,
            'project_type_id' => $projectType->id,
            'location' => 'Lima',
        ]);

        $previewResponse
            ->assertOk()
            ->assertJsonPath('project.name', 'Proyecto Excel Test')
            ->assertJsonPath('summary.valid', 1)
            ->assertJsonPath('can_import', true)
            ->assertJsonPath('rows.0.client_phone', '999888777');

        $token = $previewResponse->json('token');
        $this->assertIsString($token);

        $confirmResponse = $this->post(route('inmopro.projects.import-confirm'), [
            'token' => $token,
        ]);

        $project = Project::query()->where('name', 'Proyecto Excel Test')->first();
        $this->assertNotNull($project);

        $confirmResponse->assertRedirect(route('inmopro.projects.show', $project));

        $this->assertDatabaseHas('lots', [
            'project_id' => $project->id,
            'block' => 'A',
            'number' => 1,
            'client_name' => 'Juan Perez',
            'client_dni' => '11223344',
        ]);

        $client = Client::query()->where('dni', '11223344')->first();
        $this->assertNotNull($client);
        $this->assertSame('999888777', $client->phone);
    }

    public function test_projects_import_preview_requires_proyecto_column(): void
    {
        $user = User::factory()->create();
        $projectType = ProjectType::query()->firstOrFail();
        $this->actingAs($user);

        $file = $this->makeProjectsExcelFile([
            [
                'ITEM',
                'NOMBRE CLIENTE',
                'MZ',
                'LOTE',
                'AREA',
                'MONTO',
                'ESTADO DE LOTE',
            ],
            [1, '', 'A', 1, 100, 25000, 'LIBRE'],
        ]);

        $this->post(route('inmopro.projects.import-preview'), [
            'file' => $file,
            'project_type_id' => $projectType->id,
            'location' => 'Lima',
        ])
            ->assertOk()
            ->assertJsonPath('can_import', false);
    }

    /**
     * @param  list<list<mixed>>  $rows
     */
    private function makeProjectsExcelFile(array $rows): UploadedFile
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        foreach ($rows as $rowIndex => $row) {
            foreach ($row as $columnIndex => $value) {
                $sheet->setCellValue([$columnIndex + 1, $rowIndex + 1], $value);
            }
        }

        $path = tempnam(sys_get_temp_dir(), 'projects_excel_');
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);

        return new UploadedFile(
            $path,
            'proyecto.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );
    }
}
