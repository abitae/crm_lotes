import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type ClientType = {
    id: number;
    name: string;
    code: string;
    description?: string | null;
    sort_order?: number;
    is_active: boolean;
    clients_count?: number;
};

export default function ClientTypesShow({ clientType }: { clientType: ClientType }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Tipos de cliente', href: '/inmopro/client-types' },
        { title: clientType.name, href: `/inmopro/client-types/${clientType.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${clientType.name} - Inmopro`} />
            <div className="p-4">
                <div className="mb-6 flex items-center justify-between">
                    <h2 className="text-2xl font-black text-slate-800">{clientType.name}</h2>
                    <Link href={`/inmopro/client-types/${clientType.id}/edit`} className="rounded-xl bg-slate-900 px-4 py-2 font-bold text-white hover:bg-slate-800">Editar</Link>
                </div>
                <div className="space-y-2 text-slate-600">
                    <p>Codigo: {clientType.code}</p>
                    <p>Descripcion: {clientType.description ?? '-'}</p>
                    <p>Orden: {clientType.sort_order ?? 0}</p>
                    <p>Clientes: {clientType.clients_count ?? 0}</p>
                    <p>Estado: {clientType.is_active ? 'Activo' : 'Inactivo'}</p>
                </div>
            </div>
        </AppLayout>
    );
}
