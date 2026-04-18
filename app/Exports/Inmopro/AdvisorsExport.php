<?php

namespace App\Exports\Inmopro;

use App\Models\Inmopro\Advisor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AdvisorsExport implements FromCollection, WithHeadings
{
    /**
     * Fila de ejemplo en la plantilla (sustituir por datos reales). Ajuste ciudad y códigos a su catálogo.
     *
     * @var list<int|float|string>
     */
    private const TEMPLATE_EXAMPLE_ROW = [
        '12345678',
        'Juan Carlos',
        'Pérez López',
        '15/06/1985',
        '987654321',
        'juan.perez@ejemplo.com',
        'Lima',
        'Lima',
        'TEAM_NORTE',
        'NIVEL_1',
        25000,
        'Si',
        'jperez_ejemplo',
        '',
        'BCP',
        '',
        '',
        '15/01/2024',
    ];

    /**
     * @param  Collection<int, Advisor>  $advisors
     */
    public function __construct(
        private Collection $advisors,
        private bool $forTemplate = false,
    ) {}

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        if ($this->forTemplate) {
            return [
                'DNI (*)',
                'Nombres (*)',
                'Apellidos',
                'Fecha nacimiento (DD/MM/AAAA)',
                'Telefono (*)',
                'Email (*)',
                'Ciudad (*)',
                'Departamento (*)',
                'Codigo equipo (*)',
                'Codigo nivel (*)',
                'Cuota personal (*)',
                'Activo',
                'Username',
                'Email superior',
                'Banco',
                'Cuenta',
                'CCI (20 digitos)',
                'Fecha ingreso (DD/MM/AAAA) (*)',
            ];
        }

        return [
            'DNI',
            'Nombres',
            'Apellidos',
            'Fecha nacimiento (DD/MM/AAAA)',
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
            'Fecha ingreso (DD/MM/AAAA)',
        ];
    }

    /**
     * @return Collection<int, array<int, string|float|null>>
     */
    public function collection(): Collection
    {
        if ($this->advisors->isEmpty()) {
            if ($this->forTemplate) {
                return collect([self::TEMPLATE_EXAMPLE_ROW]);
            }

            return collect([]);
        }

        return $this->advisors->map(static function (Advisor $advisor): array {
            return [
                $advisor->dni ?? '',
                $advisor->first_name ?? $advisor->name,
                $advisor->last_name ?? '',
                $advisor->birth_date?->format('d/m/Y') ?? '',
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
                $advisor->joined_at?->format('d/m/Y') ?? '',
            ];
        });
    }
}
