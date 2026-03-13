<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAccount extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'type',
        'currency',
        'initial_balance',
        'current_balance',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'initial_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    /**
     * @return HasMany<CashEntry, $this>
     */
    public function entries(): HasMany
    {
        return $this->hasMany(CashEntry::class, 'cash_account_id');
    }

    /**
     * @return HasMany<LotPayment, $this>
     */
    public function lotPayments(): HasMany
    {
        return $this->hasMany(LotPayment::class, 'cash_account_id');
    }
}
