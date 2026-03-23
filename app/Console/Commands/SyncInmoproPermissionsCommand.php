<?php

namespace App\Console\Commands;

use App\Support\InmoproPermissionSynchronizer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('inmopro:sync-permissions')]
#[Description('Registra permisos Spatie para cada ruta nombrada inmopro.*')]
class SyncInmoproPermissionsCommand extends Command
{
    public function handle(): int
    {
        $count = InmoproPermissionSynchronizer::syncFromRoutes();
        $this->info("Permisos procesados (rutas inmopro.*): {$count}");
        $this->info('Rol super-admin actualizado con todos los permisos del guard web.');

        return self::SUCCESS;
    }
}
