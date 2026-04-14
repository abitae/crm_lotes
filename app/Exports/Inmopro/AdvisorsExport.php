<?php

namespace App\Exports\Inmopro;

use App\Models\Inmopro\Advisor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AdvisorsExport implements FromCollection, WithHeadings
{
    /**
     * @param  Collection<int, Advisor>  $advisors
     */
    public function __construct(
        private Collection $advisors
    ) {}

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'DNI',
            'Nombres',
            'Apellidos',
            'Fecha nacimiento',
            'Telefono',
            'Email',
            'Ciudad',
            'Departamento',
            'Codigo equipo',
            'Codigo nivel',
            'Cuota personal',
            'Activo',
            'Username',
            'Email superior',
            'Banco',
            'Cuenta',
            'CCI',
        ];
    }

    /**
     * @return Collection<int, array<int, string|float|null>>
     */
    public function collection(): Collection
    {
        return $this->advisors->map(static function (Advisor $advisor): array {
            return [
                $advisor->dni ?? '',
                $advisor->first_name ?? $advisor->name,
                $advisor->last_name ?? '',
                $advisor->birth_date?->toDateString() ?? '',
                $advisor->phone,
                $advisor->email,
                $advisor->city?->name ?? '',
                $advisor->city?->department ?? '',
                $advisor->team?->code ?? '',
                $advisor->level?->code ?? '',
                (float) $advisor->personal_quota,
                $advisor->is_active ? 'Si' : 'No',
                $advisor->username ?? '',
                $advisor->superior?->email ?? '',
                $advisor->bank_name ?? '',
                $advisor->bank_account_number ?? '',
                $advisor->bank_cci ?? '',
            ];
        });
    }
}
