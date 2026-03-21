<?php

namespace App\Http\Controllers\Inmopro\AccessControl;

use App\Http\Controllers\Controller;
use App\Support\InmoproPermissionSynchronizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = trim((string) $request->string('search'));

        $permissions = Permission::query()
            ->where('guard_name', 'web')
            ->when($search !== '', fn ($q) => $q->where('name', 'like', "%{$search}%"))
            ->orderBy('name')
            ->paginate(30)
            ->withQueryString();

        return Inertia::render('inmopro/access-control/permissions/index', [
            'permissions' => $permissions,
            'filters' => $request->only('search'),
        ]);
    }

    public function syncFromRoutes(): RedirectResponse
    {
        abort_unless(auth()->user()?->hasRole('super-admin'), 403);

        InmoproPermissionSynchronizer::syncFromRoutes();

        return redirect()->route('inmopro.access-control.permissions.index')
            ->with('success', 'Permisos sincronizados desde las rutas Inmopro.');
    }
}
