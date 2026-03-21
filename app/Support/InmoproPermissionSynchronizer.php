<?php

namespace App\Support;

use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

final class InmoproPermissionSynchronizer
{
    /**
     * Create missing Permission rows for every named route starting with "inmopro.".
     *
     * @return int Number of permission names processed (including existing).
     */
    public static function syncFromRoutes(): int
    {
        $processed = 0;

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! is_string($name) || ! str_starts_with($name, 'inmopro.')) {
                continue;
            }

            Permission::findOrCreate($name, 'web');
            $processed++;
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $processed;
    }
}
