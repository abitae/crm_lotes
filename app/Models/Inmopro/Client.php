<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Client extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'dni',
        'phone',
        'email',
        'referred_by',
        'client_type_id',
        'city_id',
        'advisor_id',
    ];

    /**
     * @return BelongsTo<ClientType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(ClientType::class, 'client_type_id');
    }

    /**
     * @return BelongsTo<City, $this>
     */
    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * @return BelongsTo<Advisor, $this>
     */
    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class);
    }

    /**
     * @return HasMany<Lot, $this>
     */
    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class, 'client_id');
    }

    /**
     * @return HasMany<AttentionTicket, $this>
     */
    public function attentionTickets(): HasMany
    {
        return $this->hasMany(AttentionTicket::class, 'client_id');
    }
}
