import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type City = {
    id: number;
    name: string;
    code: string;
    department?: string | null;
    sort_order?: number;
    is_active: boolean;
    clients_count?: number;
};

export default function CitiesShow({ city }: { city: City }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Ciudades', href: '/inmopro/cities' },
        { title: city.name, href: `/inmopro/cities/${city.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${city.name} - Inmopro`} />
            <div className="p-4">
                <div className="mb-6 flex items-center justify-between">
                    <h2 className="text-2xl font-black text-slate-800">{city.name}</h2>
                    <Link href={`/inmopro/cities/${city.id}/edit`} className="rounded-xl bg-slate-900 px-4 py-2 font-bold text-white hover:bg-slate-800">Editar</Link>
                </div>
                <div className="space-y-2 text-slate-600">
                    <p>Código: {city.code}</p>
                    <p>Departamento: {city.department ?? '-'}</p>
                    <p>Orden: {city.sort_order ?? 0}</p>
                    <p>Clientes: {city.clients_count ?? 0}</p>
                    <p>Estado: {city.is_active ? 'Activo' : 'Inactivo'}</p>
                </div>
            </div>
        </AppLayout>
    );
}
