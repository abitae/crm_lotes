<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdvisorReminder extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'advisor_id',
        'client_id',
        'title',
        'notes',
        'remind_at',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'remind_at' => 'datetime',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Scope: solo recordatorios no completados.
     *
     * @param  Builder<AdvisorReminder>  $query
     * @return Builder<AdvisorReminder>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->whereNull('completed_at');
    }
}
