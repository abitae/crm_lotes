<?php

namespace App\Imports\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\ClientType;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;

class ClientsImport implements ToCollection
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
        $defaultTypeId = ClientType::query()->orderBy('sort_order')->value('id');
        $defaultAdvisorId = Advisor::query()->orderBy('name')->value('id');

        if (! $defaultTypeId || ! $defaultAdvisorId) {
            return;
        }

        DB::transaction(function () use ($rows, $defaultTypeId, $defaultAdvisorId): void {
            foreach ($rows->skip(1) as $row) {
                $name = $this->cell($row, 0);
                $dni = $this->cell($row, 1);
                $phone = $this->cell($row, 2);

                if ($name === null || $dni === null || $phone === null) {
                    continue;
                }

                $clientTypeId = $this->resolveClientTypeId($this->cell($row, 5)) ?? $defaultTypeId;
                $cityId = $this->resolveCityId($this->cell($row, 6));
                $advisorId = $this->resolveAdvisorId($this->cell($row, 7)) ?? $defaultAdvisorId;

                Client::query()->updateOrCreate(
                    ['dni' => (string) $dni],
                    [
                        'name' => (string) $name,
                        'phone' => (string) $phone,
                        'email' => $this->nullableString($this->cell($row, 3)),
                        'referred_by' => $this->nullableString($this->cell($row, 4)),
                        'client_type_id' => $clientTypeId,
                        'city_id' => $cityId,
                        'advisor_id' => $advisorId,
                    ]
                );
            }
        });
    }

    private function resolveClientTypeId(mixed $value): ?int
    {
        $name = $this->nullableString($value);

        if ($name === null) {
            return null;
        }

        return ClientType::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->value('id');
    }

    private function resolveCityId(mixed $value): ?int
    {
        $name = $this->nullableString($value);

        if ($name === null) {
            return null;
        }

        return City::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->value('id');
    }

    private function resolveAdvisorId(mixed $value): ?int
    {
        $name = $this->nullableString($value);

        if ($name === null) {
            return null;
        }

        return Advisor::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->value('id');
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
