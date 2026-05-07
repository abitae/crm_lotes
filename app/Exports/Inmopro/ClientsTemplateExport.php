<?php

namespace App\Exports\Inmopro;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientsTemplateExport implements FromArray, WithHeadings
{
    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return [
            'Nombre',
            'DNI',
            'Telefono',
            'Email',
            'Referido por',
            'Tipo cliente',
            'Ciudad',
            'Asesor',
        ];
    }

    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['Cliente Ejemplo', '12345678', '987654321', 'cliente@demo.com', 'Campana digital', 'CONTADO', 'LIMA', 'Asesor Demo'],
        ];
    }
}
