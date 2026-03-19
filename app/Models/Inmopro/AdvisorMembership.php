<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdvisorMembership extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'advisor_id',
        'membership_type_id',
        'year',
        'amount',
        'start_date',
        'end_date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
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
     * @return BelongsTo<MembershipType, $this>
     */
    public function membershipType(): BelongsTo
    {
        return $this->belongsTo(MembershipType::class, 'membership_type_id');
    }

    /**
     * @return HasMany<AdvisorMembershipInstallment, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(AdvisorMembershipInstallment::class, 'advisor_membership_id')->orderBy('sequence');
    }

    /**
     * @return HasMany<AdvisorMembershipPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(AdvisorMembershipPayment::class, 'advisor_membership_id');
    }

    /**
     * Total abonado (suma de pagos).
     */
    public function totalPaid(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    /**
     * Saldo pendiente (amount - totalPaid).
     */
    public function balanceDue(): float
    {
        return max(0, (float) $this->amount - $this->totalPaid());
    }

    /**
     * Si está al día (total abonado >= amount).
     */
    public function isPaid(): bool
    {
        return $this->totalPaid() >= (float) $this->amount;
    }
}
