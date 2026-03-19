<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Hash;

class Advisor extends Model
{
    /**
     * @var list<string>
     */
    protected $hidden = [
        'pin',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'phone',
        'email',
        'username',
        'pin',
        'is_active',
        'last_login_at',
        'team_id',
        'advisor_level_id',
        'superior_id',
        'personal_quota',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'personal_quota' => 'decimal:2',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function pin(): Attribute
    {
        return Attribute::make(
            set: static function (?string $value): ?string {
                if ($value === null || $value === '') {
                    return null;
                }

                return Hash::needsRehash($value) ? Hash::make($value) : $value;
            },
        );
    }

    /**
     * @return BelongsTo<AdvisorLevel, $this>
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(AdvisorLevel::class, 'advisor_level_id');
    }

    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @return BelongsTo<Advisor, $this>
     */
    public function superior(): BelongsTo
    {
        return $this->belongsTo(Advisor::class, 'superior_id');
    }

    /**
     * @return HasMany<Advisor, $this>
     */
    public function subordinates(): HasMany
    {
        return $this->hasMany(Advisor::class, 'superior_id');
    }

    /**
     * @return HasMany<Lot, $this>
     */
    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class, 'advisor_id');
    }

    /**
     * @return HasMany<Client, $this>
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class, 'advisor_id');
    }

    /**
     * @return HasMany<Commission, $this>
     */
    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'advisor_id');
    }

    /**
     * @return HasMany<AdvisorMembership, $this>
     */
    public function memberships(): HasMany
    {
        return $this->hasMany(AdvisorMembership::class);
    }

    /**
     * @return HasMany<AdvisorApiToken, $this>
     */
    public function apiTokens(): HasMany
    {
        return $this->hasMany(AdvisorApiToken::class);
    }

    /**
     * @return HasMany<LotPreReservation, $this>
     */
    public function preReservations(): HasMany
    {
        return $this->hasMany(LotPreReservation::class);
    }

    /**
     * @return HasMany<AttentionTicket, $this>
     */
    public function attentionTickets(): HasMany
    {
        return $this->hasMany(AttentionTicket::class, 'advisor_id');
    }

    /**
     * @return HasMany<AdvisorAgendaEvent, $this>
     */
    public function agendaEvents(): HasMany
    {
        return $this->hasMany(AdvisorAgendaEvent::class, 'advisor_id');
    }

    /**
     * @return HasMany<AdvisorReminder, $this>
     */
    public function reminders(): HasMany
    {
        return $this->hasMany(AdvisorReminder::class, 'advisor_id');
    }
}
