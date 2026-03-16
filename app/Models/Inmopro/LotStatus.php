<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LotStatus extends Model
{
    public const CODE_LIBRE = 'LIBRE';

    public const CODE_PRERESERVA = 'PRERESERVA';

    public const CODE_RESERVADO = 'RESERVADO';

    public const CODE_TRANSFERIDO = 'TRANSFERIDO';

    public const CODE_CUOTAS = 'CUOTAS';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'color',
        'sort_order',
    ];

    /**
     * @return HasMany<Lot, $this>
     */
    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class, 'lot_status_id');
    }

    public static function systemCodes(): array
    {
        return [
            self::CODE_LIBRE,
            self::CODE_PRERESERVA,
            self::CODE_RESERVADO,
            self::CODE_TRANSFERIDO,
            self::CODE_CUOTAS,
        ];
    }

    public function isSystemStatus(): bool
    {
        return in_array($this->code, self::systemCodes(), true);
    }
}
