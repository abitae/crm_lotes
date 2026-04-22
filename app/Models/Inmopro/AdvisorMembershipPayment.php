<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorMembershipPayment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'advisor_membership_id',
        'advisor_membership_installment_id',
        'cash_account_id',
        'amount',
        'paid_at',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'paid_at' => 'date',
        ];
    }

    /**
     * @return BelongsTo<AdvisorMembership, $this>
     */
    public function advisorMembership(): BelongsTo
    {
        return $this->belongsTo(AdvisorMembership::class);
    }
}
