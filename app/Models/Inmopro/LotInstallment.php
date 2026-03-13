<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LotInstallment extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'lot_id',
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
     * @return BelongsTo<Lot, $this>
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    /**
     * @return HasMany<LotPayment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(LotPayment::class, 'lot_installment_id');
    }
}
