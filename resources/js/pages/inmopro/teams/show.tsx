import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Team = { id: number; name: string; code: string; description?: string | null; color?: string | null; sort_order?: number; is_active: boolean; advisors_count?: number };

export default function TeamsShow({ team }: { team: Team }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Teams comerciales', href: '/inmopro/teams' },
        { title: team.name, href: `/inmopro/teams/${team.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${team.name} - Inmopro`} />
            <div className="p-4">
                <div className="mb-6 flex items-center justify-between">
                    <h2 className="text-2xl font-black text-slate-800">{team.name}</h2>
                    <Link href={`/inmopro/teams/${team.id}/edit`} className="rounded-xl bg-slate-900 px-4 py-2 font-bold text-white hover:bg-slate-800">Editar</Link>
                </div>
                <div className="space-y-2 text-slate-600">
                    <p>Codigo: {team.code}</p>
                    <p>Descripcion: {team.description ?? '-'}</p>
                    <p>Orden: {team.sort_order ?? 0}</p>
                    <p>Vendedores: {team.advisors_count ?? 0}</p>
                    <p>Estado: {team.is_active ? 'Activo' : 'Inactivo'}</p>
                </div>
            </div>
        </AppLayout>
    );
}
