<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvisorLevel extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'direct_rate',
        'pyramid_rate',
        'color',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'direct_rate' => 'decimal:2',
            'pyramid_rate' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<Advisor, $this>
     */
    public function advisors(): HasMany
    {
        return $this->hasMany(Advisor::class, 'advisor_level_id');
    }
}
