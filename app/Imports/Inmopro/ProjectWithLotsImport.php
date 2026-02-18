<?php

namespace App\Imports\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\Lot;
use App\Models\Inmopro\LotStatus;
use App\Models\Inmopro\Project;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class ProjectWithLotsImport implements ToCollection
{
    private ?Project $project = null;

    /**
     * Formato esperado:
     * Fila 0: encabezados proyecto (Proyecto, Nombre, Ubicación, Manzanas)
     * Fila 1: datos proyecto (vacío, nombre, ubicación, "A, B, C")
     * Fila 2: vacía
     * Fila 3: encabezados lotes (Manzana, Número, Área, Precio, Observaciones, Estado, Nombre cliente, ...)
     * Fila 4+: una fila por lote.
     *
     * @param  Collection<int, Collection<int, mixed>>  $collection
     */
    public function collection(Collection $collection): void
    {
        $rows = $collection->values()->all();
        if (count($rows) < 5) {
            return;
        }

        $rowProject = $rows[1];
        $projectName = $this->cell($rowProject, 1);
        $projectLocation = $this->cell($rowProject, 2);
        $manzanasStr = $this->cell($rowProject, 3);
        $blocks = $manzanasStr
            ? array_map('trim', array_filter(explode(',', (string) $manzanasStr)))
            : [];

        if (! $projectName) {
            return;
        }

        $statusesByCode = LotStatus::all()->keyBy('code');
        $libreStatus = $statusesByCode->get('LIBRE');
        if (! $libreStatus) {
            return;
        }

        DB::transaction(function () use ($rows, $projectName, $projectLocation, $blocks, $statusesByCode, $libreStatus): void {
            $lotCount = $this->countValidLotRows($rows);
            $this->project = Project::create([
                'name' => $projectName,
                'location' => $projectLocation ?? '',
                'total_lots' => $lotCount,
                'blocks' => $blocks,
            ]);

            for ($i = 4; $i < count($rows); $i++) {
                $row = $rows[$i];
                $block = $this->cell($row, 0);
                $number = $this->cell($row, 1);
                if ($block === null && $number === null) {
                    continue;
                }
                $block = $block !== null && $block !== '' ? (string) $block : null;
                $number = is_numeric($number) ? (int) $number : null;
                if (! $block || ! $number) {
                    continue;
                }

                $estadoCode = $this->cell($row, 5);
                $lotStatus = ($estadoCode && $statusesByCode->has(strtoupper((string) $estadoCode)))
                    ? $statusesByCode->get(strtoupper((string) $estadoCode))
                    : $libreStatus;

                $advisorName = $this->cell($row, 14);
                $advisorId = null;
                if ($advisorName && trim((string) $advisorName) !== '') {
                    $advisor = Advisor::where('name', trim((string) $advisorName))->first();
                    $advisorId = $advisor?->id;
                }

                Lot::create([
                    'project_id' => $this->project->id,
                    'block' => $block,
                    'number' => $number,
                    'area' => is_numeric($this->cell($row, 2)) ? (float) $this->cell($row, 2) : null,
                    'price' => is_numeric($this->cell($row, 3)) ? (float) $this->cell($row, 3) : null,
                    'lot_status_id' => $lotStatus->id,
                    'observations' => $this->cell($row, 4) ? (string) $this->cell($row, 4) : null,
                    'client_name' => $this->cell($row, 6) ? (string) $this->cell($row, 6) : null,
                    'client_dni' => $this->cell($row, 7) ? (string) $this->cell($row, 7) : null,
                    'advance' => is_numeric($this->cell($row, 8)) ? (float) $this->cell($row, 8) : null,
                    'remaining_balance' => is_numeric($this->cell($row, 9)) ? (float) $this->cell($row, 9) : null,
                    'payment_limit_date' => $this->parseDate($this->cell($row, 10)),
                    'operation_number' => $this->cell($row, 11) ? (string) $this->cell($row, 11) : null,
                    'contract_date' => $this->parseDate($this->cell($row, 12)),
                    'contract_number' => $this->cell($row, 13) ? (string) $this->cell($row, 13) : null,
                    'advisor_id' => $advisorId,
                ]);
            }
        });
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        if ($value instanceof \DateTimeInterface) {
            return Carbon::instance($value)->format('Y-m-d');
        }
        if (is_numeric($value) && class_exists(\PhpOffice\PhpSpreadsheet\Shared\Date::class)) {
            try {
                return Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $value))->format('Y-m-d');
            } catch (\Throwable) {
                return null;
            }
        }
        try {
            return Carbon::parse((string) $value)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    /**
     * @param  array<int, Collection<int, mixed>|array<int, mixed>>  $rows
     */
    private function countValidLotRows(array $rows): int
    {
        $count = 0;
        for ($i = 4; $i < count($rows); $i++) {
            $row = $rows[$i];
            $block = $this->cell($row, 0);
            $number = $this->cell($row, 1);
            if ($block === null && $number === null) {
                continue;
            }
            $block = $block !== null && $block !== '' ? (string) $block : null;
            $number = is_numeric($number) ? (int) $number : null;
            if ($block && $number) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $row
     */
    private function cell(Collection|array $row, int $index): mixed
    {
        $arr = $row instanceof Collection ? $row->all() : $row;
        $val = $arr[$index] ?? null;

        return $val === '' || $val === null ? null : $val;
    }
}
