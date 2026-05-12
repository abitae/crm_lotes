<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvisorProfile extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'advisor_id',
        'professional_profile',
        'skills_strengths',
        'availability',
    ];

    /**
     * @return BelongsTo<Advisor, $this>
     */
    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class);
    }

    /**
     * @return HasMany<AdvisorProfileDocument, $this>
     */
    public function documents(): HasMany
    {
        return $this->hasMany(AdvisorProfileDocument::class)
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
