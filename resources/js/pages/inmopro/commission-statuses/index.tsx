import { Head, Link, router } from '@inertiajs/react';
import { Plus, Eye, Pencil, Trash2 } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type CommissionStatus = { id: number; name: string; code: string; color?: string };

export default function CommissionStatusesIndex({ commissionStatuses }: { commissionStatuses: CommissionStatus[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Estados de comision', href: '/inmopro/commission-statuses' },
    ];

    const handleDestroy = (id: number, name: string) => {
        if (window.confirm('Eliminar estado ' + name + '?')) router.delete('/inmopro/commission-statuses/' + id);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Estados de comision - Inmopro" />
            <div className="space-y-6 p-4">
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-2xl font-black text-slate-800">Estados de comision</h2>
                    </div>
                    <Link href="/inmopro/commission-statuses/create" className="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 font-bold text-white hover:bg-emerald-700">
                        <Plus className="h-5 w-5" /> Nuevo
                    </Link>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                    <table className="w-full">
                        <thead className="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th className="px-4 py-3 text-left text-sm font-bold text-slate-600">Nombre</th>
                                <th className="px-4 py-3 text-left text-sm font-bold text-slate-600">Codigo</th>
                                <th className="px-4 py-3 text-right text-sm font-bold text-slate-600">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {commissionStatuses.map((s) => (
                                <tr key={s.id}>
                                    <td className="px-4 py-3 font-medium">{s.name}</td>
                                    <td className="px-4 py-3">{s.code}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-2">
                                            <Link href={'/inmopro/commission-statuses/' + s.id}><Eye className="h-4 w-4" /></Link>
                                            <Link href={'/inmopro/commission-statuses/' + s.id + '/edit'}><Pencil className="h-4 w-4" /></Link>
                                            <button type="button" onClick={() => handleDestroy(s.id, s.name)}><Trash2 className="h-4 w-4" /></button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
