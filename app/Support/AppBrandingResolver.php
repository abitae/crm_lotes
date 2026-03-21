<?php

namespace App\Support;

use App\Models\AppBranding;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class AppBrandingResolver
{
    private const string CACHE_KEY = 'app_branding.snapshot';

    /**
     * @return array{display_name: string, logo_url: ?string}
     */
    public static function snapshot(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $row = AppBranding::query()->first();

            $displayName = filled($row?->display_name)
                ? (string) $row->display_name
                : (string) config('app.name');

            $logoUrl = null;
            if (filled($row?->logo_path)) {
                $logoUrl = Storage::disk('public')->url((string) $row->logo_path);
            }

            return [
                'display_name' => $displayName,
                'logo_url' => $logoUrl,
            ];
        });
    }

    public static function resolvedDisplayName(): string
    {
        return self::snapshot()['display_name'];
    }

    public static function logoUrl(): ?string
    {
        return self::snapshot()['logo_url'];
    }

    public static function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
