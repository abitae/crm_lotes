<?php

namespace App\Exports\Inmopro;

use App\Models\Inmopro\AdvisorLevel;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AdvisorLevelsExport implements FromCollection, WithHeadings
{
    /**
     * @param  Collection<int, AdvisorLevel>  $levels
     */
    public function __construct(
        private Collection $levels
    ) {}

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Nombre',
            'Codigo',
            'Directa %',
            'Piramidal %',
            'Color',
            'Orden',
        ];
    }

    /**
     * @return Collection<int, array<int, string|int|float|null>>
     */
    public function collection(): Collection
    {
        return $this->levels->map(static function (AdvisorLevel $level): array {
            return [
                $level->name,
                $level->code,
                (float) $level->direct_rate,
                (float) $level->pyramid_rate,
                $level->color,
                (int) $level->sort_order,
            ];
        });
    }
}
