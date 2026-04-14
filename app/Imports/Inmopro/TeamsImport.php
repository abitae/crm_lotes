<?php

namespace App\Imports\Inmopro;

use App\Models\Inmopro\Team;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class TeamsImport implements ToCollection
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
                $code = $this->nullableString($this->cell($row, 1));
                if ($name === null || $code === null) {
                    continue;
                }

                $description = $this->nullableString($this->cell($row, 2));
                $color = $this->nullableString($this->cell($row, 3));
                $sortOrder = $this->parseInt($this->cell($row, 4)) ?? 0;
                $isActive = $this->parseBool($this->cell($row, 5), default: true);
                $goal = $this->parseDecimal($this->cell($row, 6));

                Team::query()->updateOrCreate(
                    ['code' => $code],
                    [
                        'name' => $name,
                        'description' => $description,
                        'color' => $color,
                        'sort_order' => $sortOrder,
                        'is_active' => $isActive,
                        'group_sales_goal' => $goal ?? 0,
                    ]
                );
            }
        });
    }

    private function parseDecimal(mixed $value): ?float
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

    private function parseBool(mixed $value, bool $default): bool
    {
        if ($value === null || $value === '') {
            return $default;
        }

        $normalized = mb_strtolower(trim((string) $value));

        return match ($normalized) {
            'si', 'sí', '1', 'true', 'yes', 'activo' => true,
            'no', '0', 'false', 'inactivo' => false,
            default => $default,
        };
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
