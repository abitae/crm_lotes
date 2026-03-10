<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeliveryDeed extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'attention_ticket_id',
        'lot_id',
        'printed_at',
        'signed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'printed_at' => 'datetime',
            'signed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<AttentionTicket, $this>
     */
    public function attentionTicket(): BelongsTo
    {
        return $this->belongsTo(AttentionTicket::class, 'attention_ticket_id');
    }

    /**
     * @return BelongsTo<Lot, $this>
     */
    public function lot(): BelongsTo
    {
        return $this->belongsTo(Lot::class, 'lot_id');
    }
}
