<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvisorMembershipInstallment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'advisor_membership_id',
        'sequence',
        'due_date',
        'amount',
        'paid_amount',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<AdvisorMembership, $this>
     */
    public function advisorMembership(): BelongsTo
    {
        return $this->belongsTo(AdvisorMembership::class);
    }

    /**
     * @return HasMany<AdvisorMembershipPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(AdvisorMembershipPayment::class, 'advisor_membership_installment_id');
    }
}
