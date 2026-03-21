<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\InmoproPermissionSynchronizer;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AuthorizationSeeder extends Seeder
{
    public function run(): void
    {
        InmoproPermissionSynchronizer::syncFromRoutes();

        $superAdmin = Role::findOrCreate('super-admin', 'web');
        $superAdmin->syncPermissions(Permission::query()->where('guard_name', 'web')->get());

        $emails = config('rbac.super_admin_emails', []);
        if ($emails === [] && app()->environment('local', 'testing')) {
            $emails = ['abel.arana@hotmail.com'];
        }

        foreach ($emails as $email) {
            $email = trim((string) $email);
            if ($email === '') {
                continue;
            }

            User::query()->where('email', $email)->each(function (User $user) use ($superAdmin): void {
                $user->assignRole($superAdmin);
            });
        }
    }
}
