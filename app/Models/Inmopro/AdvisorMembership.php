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
        'start_date',
        'end_date',
        'amount',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Si la membresía está vencida (end_date < hoy).
     */
    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
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
        return $this->belongsTo(MembershipType::class);
    }

    /**
     * @return HasMany<AdvisorMembershipInstallment, $this>
     */
    public function installments(): HasMany
    {
        return $this->hasMany(AdvisorMembershipInstallment::class, 'advisor_membership_id');
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
