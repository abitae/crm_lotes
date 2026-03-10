<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AttentionTicket extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'advisor_id',
        'lot_id',
        'scheduled_at',
        'status',
        'notes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Advisor, $this>
     */
    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class, 'advisor_id');
    }

    /**
     * @return BelongsTo<Lot, $this>
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }

    /**
     * @return HasOne<DeliveryDeed, $this>
     */
    public function deliveryDeed(): HasOne
    {
        return $this->hasOne(DeliveryDeed::class, 'attention_ticket_id');
    }
}
