<?php

namespace App\Exports\Inmopro;

use App\Models\Inmopro\Team;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class TeamsExport implements FromCollection, WithHeadings
{
    /**
     * @param  Collection<int, Team>  $teams
     */
    public function __construct(
        private Collection $teams
    ) {}

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Nombre',
            'Codigo',
            'Descripcion',
            'Color',
            'Orden',
            'Activo',
            'Meta grupal',
        ];
    }

    /**
     * @return Collection<int, array<int, string|int|float>>
     */
    public function collection(): Collection
    {
        return $this->teams->map(static function (Team $team): array {
            return [
                $team->name,
                $team->code,
                $team->description ?? '',
                $team->color ?? '',
                (int) $team->sort_order,
                $team->is_active ? 'Si' : 'No',
                (float) ($team->group_sales_goal ?? 0),
            ];
        });
    }
}
