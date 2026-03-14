import { Head, Link, router } from '@inertiajs/react';
import { Plus, Eye, Pencil, Trash2 } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import Pagination, { type PaginationLink } from '@/components/pagination';
import { confirmDelete } from '@/lib/swal';
import type { BreadcrumbItem } from '@/types';

type AdvisorLevelRow = { id: number; name: string; code?: string; direct_rate?: string; pyramid_rate?: string; advisors_count?: number };

export default function AdvisorLevelsIndex({ advisorLevels }: { advisorLevels: { data: AdvisorLevelRow[]; links: PaginationLink[] } }) {
    const items = advisorLevels.data;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Niveles de asesor', href: '/inmopro/advisor-levels' },
    ];

    const handleDestroy = async (id: number, name: string) => {
        if (await confirmDelete(`¿Eliminar nivel "${name}"?`)) {
            router.delete('/inmopro/advisor-levels/' + id);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Niveles de asesor - Inmopro" />
            <div className="space-y-6 p-4">
                <div className="flex justify-between items-center">
                    <div>
                        <h2 className="text-2xl font-black text-slate-800">Niveles de asesor</h2>
                    </div>
                    <Link href="/inmopro/advisor-levels/create" className="flex items-center gap-2 rounded-xl bg-emerald-600 px-4 py-2.5 font-bold text-white hover:bg-emerald-700">
                        <Plus className="h-5 w-5" /> Nuevo
                    </Link>
                </div>
                <div className="rounded-2xl border border-slate-200 bg-white overflow-hidden">
                    <table className="w-full">
                        <thead className="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th className="px-4 py-3 text-left text-sm font-bold text-slate-600">Nombre</th>
                                <th className="px-4 py-3 text-right text-sm font-bold text-slate-600">Directa %</th>
                                <th className="px-4 py-3 text-right text-sm font-bold text-slate-600">Piramidal %</th>
                                <th className="px-4 py-3 text-right text-sm font-bold text-slate-600">Acciones</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-slate-100">
                            {items.map((s) => (
                                <tr key={s.id}>
                                    <td className="px-4 py-3 font-medium">{s.name}</td>
                                    <td className="px-4 py-3 text-right">{s.direct_rate ?? '—'}</td>
                                    <td className="px-4 py-3 text-right">{s.pyramid_rate ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex justify-end gap-2">
                                            <Link href={'/inmopro/advisor-levels/' + s.id}><Eye className="h-4 w-4" /></Link>
                                            <Link href={'/inmopro/advisor-levels/' + s.id + '/edit'}><Pencil className="h-4 w-4" /></Link>
                                            <button type="button" onClick={() => handleDestroy(s.id, s.name)}><Trash2 className="h-4 w-4" /></button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                    {items.length > 0 && (
                        <div className="border-t border-slate-100 px-4 py-3">
                            <Pagination links={advisorLevels.links} />
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
