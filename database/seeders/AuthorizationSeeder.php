<?php

namespace Database\Seeders;

use App\Support\InmoproPermissionSynchronizer;
use Illuminate\Database\Seeder;

class AuthorizationSeeder extends Seeder
{
    /**
     * Sincroniza permisos Inmopro y el rol super-admin (con todos los permisos web).
     * No asigna el rol a ningún usuario: la asignación es manual o vía otros seeders (p. ej. desarrollo local).
     */
    public function run(): void
    {
        InmoproPermissionSynchronizer::syncFromRoutes();
    }
}
