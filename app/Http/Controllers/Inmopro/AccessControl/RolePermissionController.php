<?php

namespace App\Http\Controllers\Inmopro\AccessControl;

use App\Http\Controllers\Controller;
use App\Http\Requests\Inmopro\AccessControl\UpdateRolePermissionsRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function edit(Role $role): Response
    {
        abort_unless($role->guard_name === 'web', 404);

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->orderBy('name')
            ->get(['id', 'name']);

        $grouped = $permissions->groupBy(function (Permission $permission): string {
            $parts = explode('.', $permission->name);

            return $parts[1] ?? 'otros';
        });

        $role->load('permissions');

        $permissionGroups = [];
        foreach ($grouped as $label => $items) {
            $permissionGroups[] = [
                'label' => (string) $label,
                'permissions' => $items->map(fn (Permission $p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                ])->values()->all(),
            ];
        }

        return Inertia::render('inmopro/access-control/roles/permissions', [
            'role' => $role,
            'permissionGroups' => $permissionGroups,
            'assignedIds' => $role->permissions->pluck('id')->all(),
        ]);
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        abort_unless($role->guard_name === 'web', 404);

        $role->syncPermissions(
            Permission::query()->whereIn('id', $request->validated('permission_ids'))->get()
        );

        return redirect()->route('inmopro.access-control.roles.index')
            ->with('success', 'Permisos del rol actualizados.');
    }
}
