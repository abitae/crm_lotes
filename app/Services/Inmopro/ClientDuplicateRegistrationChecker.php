<?php

namespace App\Services\Inmopro;

use App\Models\Inmopro\Client;

class ClientDuplicateRegistrationChecker
{
    /**
     * Busca un cliente existente con el mismo DNI (si no está vacío) o el mismo teléfono (si no está vacío).
     */
    public function findConflict(?string $dni, ?string $phone, ?int $exceptClientId = null): ?Client
    {
        $phoneTrimmed = $phone !== null ? trim((string) $phone) : '';
        $dniTrimmed = $dni !== null ? trim((string) $dni) : '';

        if ($phoneTrimmed === '' && $dniTrimmed === '') {
            return null;
        }

        return Client::query()
            ->with('advisor')
            ->where(function ($query) use ($dniTrimmed, $phoneTrimmed) {
                $first = true;
                if ($phoneTrimmed !== '') {
                    $query->where('phone', $phoneTrimmed);
                    $first = false;
                }
                if ($dniTrimmed !== '') {
                    if ($first) {
                        $query->where('dni', $dniTrimmed);
                    } else {
                        $query->orWhere('dni', $dniTrimmed);
                    }
                }
            })
            ->when($exceptClientId !== null, fn ($query) => $query->where('id', '!=', $exceptClientId))
            ->orderBy('id')
            ->first();
    }

    public function message(Client $existing): string
    {
        $advisorName = $existing->advisor?->name ?? 'otro vendedor';

        return 'Cliente ya registrado por '.$advisorName;
    }
}
