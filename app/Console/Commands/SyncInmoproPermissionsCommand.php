<?php

namespace App\Console\Commands;

use App\Support\InmoproPermissionSynchronizer;
use Illuminate\Console\Command;

class SyncInmoproPermissionsCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'inmopro:sync-permissions';

    /**
     * @var string
     */
    protected $description = 'Registra permisos Spatie para cada ruta nombrada inmopro.*';

    public function handle(): int
    {
        $count = InmoproPermissionSynchronizer::syncFromRoutes();
        $this->info("Permisos procesados (rutas inmopro.*): {$count}");
        $this->info('Rol super-admin actualizado con todos los permisos del guard web.');

        return self::SUCCESS;
    }
}
