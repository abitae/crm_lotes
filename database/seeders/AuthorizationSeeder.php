<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AuthorizationSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::query()->firstOrCreate(
            ['code' => 'confirm-lot-transfer'],
            ['name' => 'Confirmar transferencia de lotes', 'is_system' => true]
        );

        $adminRole = Role::query()->firstOrCreate(
            ['code' => 'ADMIN'],
            ['name' => 'Administrador', 'is_system' => true]
        );

        $adminRole->permissions()->syncWithoutDetaching([$permission->id]);

        User::query()
            ->where('email', 'abel.arana@hotmail.com')
            ->each(function (User $user) use ($adminRole): void {
                $user->roles()->syncWithoutDetaching([$adminRole->id]);
            });
    }
}
