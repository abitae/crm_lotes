<?php

namespace App\Exports\Inmopro;

use App\Models\Inmopro\Client;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ClientsExport implements FromCollection, WithHeadings
{
    /**
     * @param  Collection<int, Client>  $clients
     */
    public function __construct(
        private Collection $clients
    ) {}

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
     * @return Collection<int, array<int, string|null>>
     */
    public function collection(): Collection
    {
        return $this->clients->map(static function (Client $client): array {
            return [
                $client->name,
                $client->dni,
                $client->phone,
                $client->email,
                $client->referred_by,
                $client->type?->name,
                $client->city?->name,
                $client->advisor?->name,
            ];
        });
    }
}
