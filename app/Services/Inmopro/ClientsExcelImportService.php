<?php

namespace App\Services\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Client;
use App\Models\Inmopro\ClientType;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class ClientsExcelImportService
{
    private const CACHE_TTL_MINUTES = 20;

    /**
     * @var array<string, list<string>>
     */
    private const HEADER_ALIASES = [
        'name' => ['NOMBRE'],
        'dni' => ['DNI'],
        'phone' => ['TELEFONO', 'TELEFONO ', 'CELULAR', 'PHONE'],
        'email' => ['EMAIL', 'CORREO'],
        'referred_by' => ['REFERIDO POR'],
        'client_type' => ['TIPO CLIENTE'],
        'city' => ['CIUDAD'],
        'advisor' => ['ASESOR', 'VENDEDOR'],
    ];

    /**
     * @return array{
     *     summary: array{rows_read: int, valid: int, invalid: int},
     *     rows: list<array<string, mixed>>,
     *     errors: list<array{excel_row: int, field: string, message: string}>,
     *     token: string|null,
     *     can_import: bool
     * }
     */
    public function preview(UploadedFile $file): array
    {
        $loaded = $this->loadSheet($file);
        $headerMap = $this->buildHeaderMap($loaded['header']);
        $missingHeaders = $this->missingRequiredHeaders($headerMap);

        if ($missingHeaders !== []) {
            return [
                'summary' => [
                    'rows_read' => 0,
                    'valid' => 0,
                    'invalid' => 0,
                ],
                'rows' => [],
                'errors' => array_map(
                    fn (string $header): array => [
                        'excel_row' => 1,
                        'field' => 'header',
                        'message' => 'Falta la columna obligatoria: '.$header,
                    ],
                    $missingHeaders
                ),
                'token' => null,
                'can_import' => false,
            ];
        }

        $rows = [];
        $errors = [];
        $apply = [];
        $rowsRead = 0;
        $seenDnis = [];

        foreach ($loaded['rows'] as $row) {
            $cells = $row['cells'];
            if ($this->isRowEmpty($cells)) {
                continue;
            }

            $rowsRead++;
            $excelRow = $row['excel_row'];
            $rowErrors = [];

            $name = $this->cellStringByField($cells, $headerMap, 'name');
            $dni = $this->normalizeDni($this->cellStringByField($cells, $headerMap, 'dni'));
            $phone = $this->normalizePhone($this->cellStringByField($cells, $headerMap, 'phone'));
            $email = $this->cellStringByField($cells, $headerMap, 'email');
            $referredBy = $this->cellStringByField($cells, $headerMap, 'referred_by');
            $clientTypeName = $this->cellStringByField($cells, $headerMap, 'client_type');
            $cityName = $this->cellStringByField($cells, $headerMap, 'city');
            $advisorName = $this->cellStringByField($cells, $headerMap, 'advisor');

            if ($name === null) {
                $rowErrors[] = ['field' => 'name', 'message' => 'El nombre es obligatorio.'];
            }
            if ($dni === null) {
                $rowErrors[] = ['field' => 'dni', 'message' => 'El DNI es obligatorio y debe tener 8 digitos.'];
            }
            if ($phone === null) {
                $rowErrors[] = ['field' => 'phone', 'message' => 'El telefono es obligatorio.'];
            }

            if ($dni !== null) {
                if (isset($seenDnis[$dni])) {
                    $rowErrors[] = ['field' => 'dni', 'message' => 'El DNI esta duplicado dentro del archivo.'];
                } else {
                    $seenDnis[$dni] = true;
                }
            }

            $clientTypeId = $this->resolveClientTypeId($clientTypeName);
            if ($clientTypeId === null) {
                $rowErrors[] = ['field' => 'client_type', 'message' => 'El tipo de cliente no existe.'];
            }

            $advisorId = $this->resolveAdvisorId($advisorName);
            if ($advisorId === null) {
                $rowErrors[] = ['field' => 'advisor', 'message' => 'El asesor no existe.'];
            }

            $cityId = $this->resolveCityId($cityName);
            if ($cityName !== null && $cityId === null) {
                $rowErrors[] = ['field' => 'city', 'message' => 'La ciudad no existe.'];
            }

            if ($email !== null && ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $rowErrors[] = ['field' => 'email', 'message' => 'El email no tiene un formato valido.'];
            }

            $existingByDni = $dni !== null ? Client::query()->where('dni', $dni)->first() : null;
            $existingByPhone = $phone !== null ? Client::query()->where('phone', $phone)->first() : null;

            if ($existingByPhone !== null && $existingByDni !== null && $existingByPhone->id !== $existingByDni->id) {
                $rowErrors[] = ['field' => 'phone', 'message' => 'El telefono pertenece a otro cliente.'];
            }

            if ($existingByPhone !== null && $existingByDni === null) {
                $rowErrors[] = ['field' => 'phone', 'message' => 'El telefono ya esta registrado en otro cliente.'];
            }

            foreach ($rowErrors as $rowError) {
                $errors[] = [
                    'excel_row' => $excelRow,
                    'field' => $rowError['field'],
                    'message' => $rowError['message'],
                ];
            }

            $rows[] = [
                'excel_row' => $excelRow,
                'name' => $name,
                'dni' => $dni,
                'phone' => $phone,
                'email' => $email,
                'client_type' => $clientTypeName,
                'city' => $cityName,
                'advisor' => $advisorName,
                'action' => $existingByDni ? 'update' : 'create',
                'errors' => array_map(fn (array $error): string => $error['message'], $rowErrors),
            ];

            if ($rowErrors === []) {
                $apply[] = [
                    'match_dni' => $dni,
                    'payload' => [
                        'name' => $name,
                        'dni' => $dni,
                        'phone' => $phone,
                        'email' => $email,
                        'referred_by' => $referredBy,
                        'client_type_id' => $clientTypeId,
                        'city_id' => $cityId,
                        'advisor_id' => $advisorId,
                    ],
                ];
            }
        }

        $valid = count($apply);
        $invalid = count($rows) - $valid;
        $canImport = $errors === [] && $valid > 0;
        $token = null;

        if ($canImport) {
            $userId = auth()->id();
            if (! is_int($userId)) {
                throw new RuntimeException('Usuario no autenticado.');
            }

            $token = (string) Str::uuid();
            Cache::put(
                $this->cacheKey($userId, $token),
                ['apply' => $apply],
                now()->addMinutes(self::CACHE_TTL_MINUTES)
            );
        }

        return [
            'summary' => [
                'rows_read' => $rowsRead,
                'valid' => $valid,
                'invalid' => $invalid,
            ],
            'rows' => $rows,
            'errors' => $errors,
            'token' => $token,
            'can_import' => $canImport,
        ];
    }

    public function confirm(string $token, User $user): void
    {
        $cached = Cache::pull($this->cacheKey($user->id, $token));

        if (! is_array($cached) || ! isset($cached['apply']) || ! is_array($cached['apply'])) {
            throw new RuntimeException('La validacion expiro o no es valida. Vuelva a cargar el archivo.');
        }

        DB::transaction(function () use ($cached): void {
            foreach ($cached['apply'] as $row) {
                Client::query()->updateOrCreate(
                    ['dni' => $row['match_dni']],
                    $row['payload']
                );
            }
        });
    }

    /**
     * @return array{header: array<int, mixed>, rows: list<array{excel_row: int, cells: array<int, mixed>}>}
     */
    private function loadSheet(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getSheet(0);
        $rows = $sheet->toArray(null, true, true, false);

        if ($rows === []) {
            throw new RuntimeException('El archivo Excel no contiene filas.');
        }

        $header = array_values((array) $rows[0]);
        $dataRows = [];

        foreach (array_slice($rows, 1) as $index => $row) {
            $dataRows[] = [
                'excel_row' => $index + 2,
                'cells' => array_values((array) $row),
            ];
        }

        return [
            'header' => $header,
            'rows' => $dataRows,
        ];
    }

    /**
     * @param  array<int, mixed>  $header
     * @return array<string, int>
     */
    private function buildHeaderMap(array $header): array
    {
        $normalizedHeader = [];
        foreach ($header as $index => $value) {
            $normalizedHeader[$this->normalizeHeader((string) $value)] = $index;
        }

        $map = [];
        foreach (self::HEADER_ALIASES as $field => $aliases) {
            foreach ($aliases as $alias) {
                $normalizedAlias = $this->normalizeHeader($alias);
                if (array_key_exists($normalizedAlias, $normalizedHeader)) {
                    $map[$field] = $normalizedHeader[$normalizedAlias];
                    break;
                }
            }
        }

        return $map;
    }

    /**
     * @param  array<string, int>  $headerMap
     * @return list<string>
     */
    private function missingRequiredHeaders(array $headerMap): array
    {
        $required = [
            'name' => 'Nombre',
            'dni' => 'DNI',
            'phone' => 'Telefono',
            'client_type' => 'Tipo cliente',
            'advisor' => 'Asesor',
        ];

        $missing = [];
        foreach ($required as $field => $label) {
            if (! array_key_exists($field, $headerMap)) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /**
     * @param  array<int, mixed>  $cells
     * @param  array<string, int>  $headerMap
     */
    private function cellStringByField(array $cells, array $headerMap, string $field): ?string
    {
        if (! isset($headerMap[$field])) {
            return null;
        }

        $value = $cells[$headerMap[$field]] ?? null;
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);

        return $text === '' ? null : $text;
    }

    /**
     * @param  array<int, mixed>  $cells
     */
    private function isRowEmpty(array $cells): bool
    {
        foreach ($cells as $value) {
            if ($value !== null && trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeHeader(string $value): string
    {
        $value = Str::of($value)
            ->ascii()
            ->upper()
            ->replaceMatches('/[^A-Z0-9]+/', ' ')
            ->trim()
            ->value();

        return preg_replace('/\s+/', ' ', $value) ?? '';
    }

    private function normalizeDni(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $value);

        return $digits !== null && strlen($digits) === 8 ? $digits : null;
    }

    private function normalizePhone(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim($value);

        return $text === '' ? null : $text;
    }

    private function resolveClientTypeId(?string $name): ?int
    {
        if ($name === null) {
            return null;
        }

        $id = ClientType::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function resolveCityId(?string $name): ?int
    {
        if ($name === null) {
            return null;
        }

        $id = City::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function resolveAdvisorId(?string $name): ?int
    {
        if ($name === null) {
            return null;
        }

        $id = Advisor::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function cacheKey(int $userId, string $token): string
    {
        return 'clients_excel_import_confirm:'.$userId.':'.$token;
    }
}
