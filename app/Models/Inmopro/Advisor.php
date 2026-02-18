<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Advisor extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'advisor_level_id',
        'superior_id',
        'personal_quota',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_quota' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<AdvisorLevel, $this>
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(AdvisorLevel::class, 'advisor_level_id');
    }

    /**
     * @return BelongsTo<Advisor, $this>
     */
    public function superior(): BelongsTo
    {
        return $this->belongsTo(Advisor::class, 'superior_id');
    }

    /**
     * @return HasMany<Advisor, $this>
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Advisor::class, 'superior_id');
    }

    /**
     * @return HasMany<Lot, $this>
     */
    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class, 'advisor_id');
    }

    /**
     * @return HasMany<Commission, $this>
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'advisor_id');
    }
}
