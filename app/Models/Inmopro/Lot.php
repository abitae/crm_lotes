<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lot extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'project_id',
        'block',
        'number',
        'area',
        'price',
        'lot_status_id',
        'client_id',
        'advisor_id',
        'client_name',
        'client_dni',
        'advance',
        'remaining_balance',
        'payment_limit_date',
        'operation_number',
        'contract_date',
        'contract_number',
        'notarial_transfer_date',
        'observations',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'area' => 'decimal:2',
            'price' => 'decimal:2',
            'advance' => 'decimal:2',
            'remaining_balance' => 'decimal:2',
            'payment_limit_date' => 'date',
            'contract_date' => 'date',
            'notarial_transfer_date' => 'date',
        ];
    }

    /**
     * @return BelongsTo<Project, $this>
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    /**
     * @return BelongsTo<LotStatus, $this>
     */
    public function status(): BelongsTo
    {
        return $this->belongsTo(LotStatus::class, 'lot_status_id');
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'client_id');
    }

    /**
     * @return BelongsTo<Advisor, $this>
     */
    public function advisor(): BelongsTo
    {
        return $this->belongsTo(Advisor::class, 'advisor_id');
    }

    /**
     * @return HasMany<Commission, $this>
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'lot_id');
    }
}
