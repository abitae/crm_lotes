<?php

namespace App\Support;

use App\Models\Inmopro\Datero;

final class DateroRegistrationUrl
{
    public static function forDatero(Datero $datero): ?string
    {
        if ($datero->invite_token === null || $datero->invite_token === '') {
            return null;
        }

        return route('public.datero-registration.show', [
            'token' => $datero->invite_token,
        ], absolute: true);
    }
}
