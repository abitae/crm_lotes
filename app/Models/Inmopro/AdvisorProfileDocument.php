<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorProfileDocument extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'advisor_profile_id',
        'title',
        'file_name',
        'file_path',
        'mime_type',
        'file_size',
        'sort_order',
    ];

    /**
     * @return BelongsTo<AdvisorProfile, $this>
     */
    public function profile(): BelongsTo
    {
        return $this->belongsTo(AdvisorProfile::class, 'advisor_profile_id');
    }
}
