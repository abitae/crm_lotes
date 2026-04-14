<?php

namespace App\Services\Inmopro;

use App\Models\Inmopro\Advisor;
use App\Models\Inmopro\AdvisorLevel;
use App\Models\Inmopro\City;
use App\Models\Inmopro\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class AdvisorsExcelImportService
{
    private const CACHE_TTL_MINUTES = 20;

    /**
     * Columnas: DNI, Nombres, Apellidos, Fecha nacimiento, Telefono, Email, Ciudad, Departamento,
     * Codigo equipo, Codigo nivel, Cuota personal, Activo, Username, Email superior, Banco, Cuenta, CCI.
     */
    private const COL_DNI = 0;

    private const COL_FIRST_NAME = 1;

    private const COL_LAST_NAME = 2;

    private const COL_BIRTH = 3;

    private const COL_PHONE = 4;

    private const COL_EMAIL = 5;

    private const COL_CITY = 6;

    private const COL_DEPT = 7;

    private const COL_TEAM = 8;

    private const COL_LEVEL = 9;

    private const COL_QUOTA = 10;

    private const COL_ACTIVE = 11;

    private const COL_USERNAME = 12;

    private const COL_SUPERIOR_EMAIL = 13;

    private const COL_BANK = 14;

    private const COL_ACCOUNT = 15;

    private const COL_CCI = 16;

    /**
     * @return array{rows: list<array<string, mixed>>, summary: array{valid: int, invalid: int}, token: string|null, can_confirm: bool}
     */
    public function preview(UploadedFile $file): array
    {
        $sheetRows = $this->loadSheetRows($file);
        $seenDnis = [];
        $previewRows = [];
        $validApply = [];
        $superiorLinks = [];

        foreach ($sheetRows as $item) {
            $excelRow = $item['excel_row'];
            $cells = $item['cells'];
            if ($this->isRowEmpty($cells)) {
                continue;
            }

            $rowResult = [
                'excel_row' => $excelRow,
                'dni' => null,
                'status' => 'invalid',
                'action' => null,
                'errors' => [],
            ];

            $dniNorm = $this->normalizeDni($this->cellString($cells, self::COL_DNI));
            $rowResult['dni'] = $dniNorm;

            if ($dniNorm === null) {
                $rowResult['errors'][] = 'El DNI es obligatorio y debe tener 8 dígitos.';
                $previewRows[] = $rowResult;

                continue;
            }

            if (isset($seenDnis[$dniNorm])) {
                $rowResult['errors'][] = 'DNI duplicado en el archivo.';
                $previewRows[] = $rowResult;

                continue;
            }
            $seenDnis[$dniNorm] = true;

            $firstName = $this->cellString($cells, self::COL_FIRST_NAME);
            $phone = $this->cellString($cells, self::COL_PHONE);
            $email = $this->cellString($cells, self::COL_EMAIL);

            if ($firstName === null || $phone === null || $email === null) {
                $rowResult['errors'][] = 'Nombres, teléfono y email son obligatorios.';
                $previewRows[] = $rowResult;

                continue;
            }

            $cityName = $this->cellString($cells, self::COL_CITY);
            $department = $this->cellString($cells, self::COL_DEPT);
            $teamCode = $this->cellString($cells, self::COL_TEAM);
            $levelCode = $this->cellString($cells, self::COL_LEVEL);

            $cityId = $this->resolveCityId($cityName, $department);
            $teamId = $this->resolveTeamId($teamCode);
            $levelId = $this->resolveLevelId($levelCode);

            if ($cityId === null) {
                $rowResult['errors'][] = 'Ciudad no encontrada o inactiva.';
            }
            if ($teamId === null) {
                $rowResult['errors'][] = 'Código de equipo no válido.';
            }
            if ($levelId === null) {
                $rowResult['errors'][] = 'Código de nivel no válido.';
            }

            $cci = $this->cellString($cells, self::COL_CCI);
            if ($cci !== null && $cci !== '' && ! preg_match('/^[0-9]{20}$/', $cci)) {
                $rowResult['errors'][] = 'El CCI debe tener exactamente 20 dígitos.';
            }

            $existingByDni = Advisor::query()->where('dni', $dniNorm)->first();
            $existingByEmail = Advisor::query()->where('email', $email)->first();

            if ($existingByDni === null && $existingByEmail !== null) {
                $rowResult['errors'][] = 'El email ya está registrado con otro DNI.';
            }

            if ($existingByDni !== null && $existingByEmail !== null && $existingByDni->id !== $existingByEmail->id) {
                $rowResult['errors'][] = 'El email pertenece a otro vendedor.';
            }

            if ($rowResult['errors'] !== []) {
                $previewRows[] = $rowResult;

                continue;
            }

            $birth = $this->cellString($cells, self::COL_BIRTH);
            $quota = $this->parseDecimal($cells, self::COL_QUOTA);
            $isActive = $this->parseBool($cells, self::COL_ACTIVE, true);
            $username = $this->cellString($cells, self::COL_USERNAME);
            $superiorEmail = $this->cellString($cells, self::COL_SUPERIOR_EMAIL);

            $payload = [
                'first_name' => $firstName,
                'last_name' => $this->cellString($cells, self::COL_LAST_NAME),
                'birth_date' => $birth !== null && $birth !== '' ? $birth : null,
                'phone' => $phone,
                'email' => $email,
                'city_id' => $cityId,
                'team_id' => $teamId,
                'advisor_level_id' => $levelId,
                'personal_quota' => $quota ?? 0,
                'is_active' => $isActive,
                'superior_id' => null,
                'bank_name' => $this->cellString($cells, self::COL_BANK),
                'bank_account_number' => $this->cellString($cells, self::COL_ACCOUNT),
                'bank_cci' => ($cci !== null && $cci !== '') ? $cci : null,
                'dni' => $dniNorm,
            ];

            $action = $existingByDni !== null ? 'update' : 'create';

            if ($action === 'update' && $username !== null && $username !== '') {
                $otherUsername = Advisor::query()
                    ->where('username', $username)
                    ->whereKeyNot($existingByDni->id)
                    ->exists();
                if ($otherUsername) {
                    $rowResult['errors'][] = 'El usuario (username) ya está en uso por otro vendedor.';
                    $previewRows[] = $rowResult;

                    continue;
                }
                $payload['username'] = $username;
            }

            if ($action === 'create' && $username !== null && $username !== '') {
                if (Advisor::query()->where('username', $username)->exists()) {
                    $rowResult['errors'][] = 'El usuario (username) ya está en uso.';
                    $previewRows[] = $rowResult;

                    continue;
                }
                $payload['username'] = $username;
            }

            $rowResult['status'] = 'valid';
            $rowResult['action'] = $action;
            $rowResult['errors'] = [];
            $previewRows[] = $rowResult;

            $applyRow = [
                'dni' => $dniNorm,
                'action' => $action,
                'payload' => $payload,
                'superior_email' => $superiorEmail,
            ];
            if ($action === 'create') {
                $applyRow['generated_username'] = $payload['username'] ?? (string) Str::of($email)->before('@')->slug('_');
            }

            $validApply[] = $applyRow;
            if ($superiorEmail !== null && $superiorEmail !== '') {
                $superiorLinks[] = ['advisor_dni' => $dniNorm, 'superior_email' => $superiorEmail];
            }
        }

        $valid = count(array_filter($previewRows, fn (array $r) => $r['status'] === 'valid'));
        $invalid = count(array_filter($previewRows, fn (array $r) => $r['status'] === 'invalid'));

        $token = null;
        $canConfirm = $invalid === 0 && $valid > 0;

        if ($canConfirm) {
            $token = (string) Str::uuid();
            $userId = auth()->id();
            if (! is_int($userId)) {
                throw new RuntimeException('Usuario no autenticado.');
            }
            Cache::put(
                $this->cacheKey($userId, $token),
                [
                    'apply' => $validApply,
                    'superior_links' => $superiorLinks,
                ],
                now()->addMinutes(self::CACHE_TTL_MINUTES)
            );
        }

        return [
            'rows' => $previewRows,
            'summary' => [
                'valid' => $valid,
                'invalid' => $invalid,
            ],
            'token' => $token,
            'can_confirm' => $canConfirm,
        ];
    }

    public function confirm(string $token, User $user): void
    {
        $key = $this->cacheKey($user->id, $token);
        $cached = Cache::pull($key);
        if (! is_array($cached) || ! isset($cached['apply'])) {
            throw new RuntimeException('La previsualización expiró o no es válida. Vuelva a cargar el archivo.');
        }

        $apply = $cached['apply'];
        $superiorLinks = $cached['superior_links'] ?? [];

        DB::transaction(function () use ($apply, $superiorLinks): void {
            foreach ($apply as $row) {
                $dni = $row['dni'];
                $payload = $row['payload'];
                $action = $row['action'];

                if ($action === 'create') {
                    $username = $row['generated_username'] ?? (string) Str::of($payload['email'])->before('@')->slug('_');
                    Advisor::query()->create(array_merge($payload, [
                        'username' => $username,
                        'pin' => '123456',
                    ]));
                } else {
                    $advisor = Advisor::query()->where('dni', $dni)->firstOrFail();
                    $advisor->update($payload);
                }
            }

            foreach ($superiorLinks as $link) {
                $sub = Advisor::query()->where('dni', $link['advisor_dni'])->first();
                $sup = Advisor::query()->where('email', $link['superior_email'])->first();
                if ($sub && $sup && $sub->id !== $sup->id) {
                    $sub->update(['superior_id' => $sup->id]);
                }
            }
        });
    }

    /**
     * @return list<array{excel_row: int, cells: array<int, mixed>}>
     */
    private function loadSheetRows(UploadedFile $file): array
    {
        $spreadsheet = IOFactory::load($file->getRealPath());
        $all = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
        $out = [];
        foreach (array_slice($all, 1) as $i => $row) {
            $cells = is_array($row) ? array_values($row) : [];
            $out[] = [
                'excel_row' => $i + 2,
                'cells' => $cells,
            ];
        }

        return $out;
    }

    /**
     * @param  array<int, mixed>  $cells
     */
    private function isRowEmpty(array $cells): bool
    {
        foreach ($cells as $v) {
            if ($v !== null && trim((string) $v) !== '') {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, mixed>  $cells
     */
    private function cellString(array $cells, int $index): ?string
    {
        if (! array_key_exists($index, $cells)) {
            return null;
        }
        $text = trim((string) ($cells[$index] ?? ''));

        return $text === '' ? null : $text;
    }

    /**
     * @param  array<int, mixed>  $cells
     */
    private function parseDecimal(array $cells, int $index): ?float
    {
        $v = $cells[$index] ?? null;
        if ($v === null || $v === '') {
            return null;
        }

        return (float) $v;
    }

    /**
     * @param  array<int, mixed>  $cells
     */
    private function parseBool(array $cells, int $index, bool $default): bool
    {
        $v = $cells[$index] ?? null;
        if ($v === null || $v === '') {
            return $default;
        }

        $normalized = mb_strtolower(trim((string) $v));

        return match ($normalized) {
            'si', 'sí', '1', 'true', 'yes', 'activo' => true,
            'no', '0', 'false', 'inactivo' => false,
            default => $default,
        };
    }

    private function normalizeDni(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }
        $digits = preg_replace('/\D/', '', trim($value));

        if ($digits === '' || strlen($digits) !== 8) {
            return null;
        }

        return $digits;
    }

    private function resolveCityId(?string $cityName, ?string $department): ?int
    {
        if ($cityName === null) {
            return null;
        }

        $cityLower = mb_strtolower($cityName);

        $query = City::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(name) = ?', [$cityLower]);

        if ($department !== null && $department !== '') {
            $deptLower = mb_strtolower($department);
            $id = (clone $query)->whereRaw('LOWER(department) = ?', [$deptLower])->value('id');
            if ($id !== null) {
                return (int) $id;
            }
        }

        $id = City::query()
            ->where('is_active', true)
            ->whereRaw('LOWER(name) = ?', [$cityLower])
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function resolveTeamId(?string $code): ?int
    {
        if ($code === null) {
            return null;
        }

        $id = Team::query()
            ->whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))])
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function resolveLevelId(?string $code): ?int
    {
        if ($code === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($code));

        $id = AdvisorLevel::query()
            ->whereRaw('LOWER(code) = ?', [$normalized])
            ->value('id');

        if ($id !== null) {
            return (int) $id;
        }

        $id = AdvisorLevel::query()
            ->whereRaw('LOWER(name) = ?', [$normalized])
            ->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function cacheKey(int $userId, string $token): string
    {
        return 'advisor_excel_import_confirm:'.$userId.':'.$token;
    }
}
