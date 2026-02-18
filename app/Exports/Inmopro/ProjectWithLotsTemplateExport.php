<?php

namespace App\Exports\Inmopro;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;

/**
 * Plantilla Excel: proyecto (nombre, ubicación, manzanas) + filas de lotes con campos de reserva.
 */
class ProjectWithLotsTemplateExport implements FromCollection
{
    /**
     * @return Collection<int, array<int, string|int|float|null>>
     */
    public function collection(): Collection
    {
        return collect([
            ['Proyecto', 'Nombre', 'Ubicación', 'Manzanas'],
            ['', 'Ejemplo Residencial Norte', 'Lima', 'A, B, C'],
            [],
            [
                'Manzana',
                'Número',
                'Área',
                'Precio',
                'Observaciones',
                'Estado',
                'Nombre cliente',
                'DNI cliente',
                'Adelanto - separación',
                'Monto restante',
                'Fecha límite de pago',
                'N° de operación S.',
                'Fecha de contrato',
                'Nº de contrato',
                'Asesor',
            ],
            ['A', 1, 120, 25000, '', 'LIBRE', '', '', '', '', '', '', '', '', ''],
            ['A', 2, 120, 25000, '', 'RESERVADO', 'Juan Pérez', '12345678', 5000, 20000, '2025-12-31', 'OP-001', '2025-02-01', 'CT-001', 'María García'],
            ['A', 3, 150, 32000, '', 'LIBRE', '', '', '', '', '', '', '', '', ''],
            ['B', 1, 120, 25000, '', 'LIBRE', '', '', '', '', '', '', '', '', ''],
            ['B', 2, 120, 25000, '', 'RESERVADO', 'Ana López', '87654321', 3000, 22000, '2025-11-15', 'OP-002', '2025-01-15', 'CT-002', 'Carlos Ruiz'],
        ]);
    }
}
