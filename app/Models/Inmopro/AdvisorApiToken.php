<?php

namespace App\Models\Inmopro;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AdvisorApiToken extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'advisor_id',
        'name',
        'token',
        'last_used_at',
        'expires_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'expires_at' => 'datetime',
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
     * @return array{plain_text_token: string, token: self}
     */
    public static function issueFor(Advisor $advisor, string $name = 'Cazador'): array
    {
        $plainTextToken = Str::random(80);
        $token = $advisor->apiTokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken),
        ]);

        return [
            'plain_text_token' => $plainTextToken,
            'token' => $token,
        ];
    }
}
