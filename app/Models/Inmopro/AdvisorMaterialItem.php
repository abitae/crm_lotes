<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorMaterialItem extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'advisor_id',
        'advisor_material_type_id',
        'delivered_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Advisor, $this>
     */
    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class);
    }

    /**
     * @return BelongsTo<AdvisorMaterialType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(AdvisorMaterialType::class, 'advisor_material_type_id');
    }
}
