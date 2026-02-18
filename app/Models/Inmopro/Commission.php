<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Commission extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'lot_id',
        'advisor_id',
        'amount',
        'percentage',
        'type',
        'commission_status_id',
        'date',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'percentage' => 'decimal:2',
            'date' => 'date',
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
     * @return BelongsTo<Advisor, $this>
     */
    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class, 'advisor_id');
    }

    /**
     * @return BelongsTo<CommissionStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(CommissionStatus::class, 'commission_status_id');
    }
}
