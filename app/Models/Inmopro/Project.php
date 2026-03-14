<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'location',
        'total_lots',
        'blocks',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'total_lots' => 'integer',
        ];
    }

    /**
     * @return HasMany<Lot, $this>
     */
    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class, 'project_id');
    }

    /**
     * @return HasMany<AttentionTicket, $this>
     */
    public function attentionTickets(): HasMany
    {
        return $this->hasMany(AttentionTicket::class, 'project_id');
    }
}
