<?php

namespace Tests\Feature\Inmopro;

use App\Support\InmoproPermissionSynchronizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class InmoproSuperAdminPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_has_exactly_all_web_permissions_after_sync_from_routes(): void
    {
        InmoproPermissionSynchronizer::syncFromRoutes();

        $superAdmin = Role::findByName('super-admin', 'web');

        $expectedNames = Permission::query()
            ->where('guard_name', 'web')
            ->pluck('name')
            ->sort()
            ->values()
            ->all();

        $this->assertNotEmpty($expectedNames, 'Debe haber al menos un permiso web (rutas inmopro.*).');

        $actualNames = $superAdmin->getPermissionNames()
            ->sort()
            ->values()
            ->all();

        $this->assertSame($expectedNames, $actualNames);
    }

    public function test_grant_all_web_permissions_picks_up_manually_created_web_permissions(): void
    {
        InmoproPermissionSynchronizer::syncFromRoutes();

        Permission::findOrCreate('custom.manual.web-permission', 'web');

        InmoproPermissionSynchronizer::grantAllWebPermissionsToSuperAdmin();

        $superAdmin = Role::findByName('super-admin', 'web');

        $this->assertTrue($superAdmin->hasPermissionTo('custom.manual.web-permission'));
    }
}
