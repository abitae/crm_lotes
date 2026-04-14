<?php

namespace App\Imports\Inmopro;

use App\Models\Inmopro\AdvisorLevel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class AdvisorLevelsImport implements ToCollection
{
    /**
     * @param  Collection<int, Collection<int, mixed>>  $collection
     */
    public function collection(Collection $collection): void
    {
        if ($collection->isEmpty()) {
            return;
        }

        $rows = $collection->values();

        DB::transaction(function () use ($rows): void {
            foreach ($rows->skip(1) as $row) {
                $name = $this->nullableString($this->cell($row, 0));
                if ($name === null) {
                    continue;
                }

                $code = $this->nullableString($this->cell($row, 1));
                $direct = $this->parseRate($this->cell($row, 2));
                $pyramid = $this->parseRate($this->cell($row, 3));
                $color = $this->nullableString($this->cell($row, 4));
                $sortOrder = $this->parseInt($this->cell($row, 5));

                $payload = [
                    'name' => $name,
                    'direct_rate' => $direct ?? 0,
                    'pyramid_rate' => $pyramid ?? 0,
                    'color' => $color,
                    'sort_order' => $sortOrder ?? 0,
                ];

                if ($code !== null) {
                    AdvisorLevel::query()->updateOrCreate(
                        ['code' => $code],
                        $payload + ['code' => $code]
                    );
                } else {
                    $existing = AdvisorLevel::query()
                        ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                        ->first();
                    if ($existing) {
                        $existing->update($payload);
                    } else {
                        AdvisorLevel::query()->create($payload + ['code' => null]);
                    }
                }
            }
        });
    }

    private function parseRate(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (float) $value;
    }

    private function parseInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /**
     * @param  Collection<int, mixed>|array<int, mixed>  $row
     */
    private function cell(Collection|array $row, int $index): mixed
    {
        $values = $row instanceof Collection ? $row->all() : $row;

        return $values[$index] ?? null;
    }
}
