<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppBranding extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'display_name',
        'logo_path',
    ];

    public static function current(): self
    {
        $branding = self::query()->first();

        if ($branding === null) {
            $branding = self::query()->create([
                'display_name' => null,
                'logo_path' => null,
            ]);
        }

        return $branding;
    }
}
