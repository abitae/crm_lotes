<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MembershipType extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'months',
        'amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'months' => 'integer',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<AdvisorMembership, $this>
     */
    public function advisorMemberships(): HasMany
    {
        return $this->hasMany(AdvisorMembership::class, 'membership_type_id');
    }
}
