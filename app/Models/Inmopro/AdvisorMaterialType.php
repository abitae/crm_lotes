<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvisorMaterialType extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'name',
        'sort_order',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<AdvisorMaterialItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(AdvisorMaterialItem::class, 'advisor_material_type_id');
    }
}
