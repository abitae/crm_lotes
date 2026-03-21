<?php

namespace App\Models\Inmopro;

use Database\Factories\Inmopro\TeamFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    /** @use HasFactory<TeamFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'color',
        'sort_order',
        'is_active',
        'group_sales_goal',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'group_sales_goal' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<Advisor, $this>
     */
    public function advisors(): HasMany
    {
        return $this->hasMany(Advisor::class);
    }
}
