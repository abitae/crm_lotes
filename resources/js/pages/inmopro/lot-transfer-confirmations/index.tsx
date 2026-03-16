import { Head, Link, router, useForm } from '@inertiajs/react';
import { Eye, Search } from 'lucide-react';
import type { FormEvent } from 'react';
import AppLayout from '@/layouts/app-layout';
import Pagination, { type PaginationLink } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

type ProjectOption = { id: number; name: string };
type LotItem = {
    id: number;
    block: string;
    number: number;
    price?: string | null;
    contract_date?: string | null;
    project?: { id: number; name: string } | null;
    client?: { id: number; name: string; dni?: string | null; phone?: string | null } | null;
    advisor?: { id: number; name: string } | null;
};

export default function LotTransferConfirmationsIndex({
    lots,
    filters,
    projects,
}: {
    lots: { data: LotItem[]; links: PaginationLink[] };
    filters: { search?: string; project_id?: string };
    projects: ProjectOption[];
}) {
    const form = useForm({
        search: filters.search ?? '',
        project_id: filters.project_id ?? '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Confirmación de transferencias', href: '/inmopro/lot-transfer-confirmations' },
    ];

    const submitFilters = (event: FormEvent) => {
        event.preventDefault();

        router.get(
            '/inmopro/lot-transfer-confirmations',
            {
                search: form.data.search || undefined,
                project_id: form.data.project_id || undefined,
            },
            { preserveState: true }
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Confirmación de transferencias - Inmopro" />

            <div className="space-y-6 p-4">
                <div>
                    <h2 className="text-2xl font-black text-slate-800">
                        Confirmación de transferencias
                    </h2>
                    <p className="text-sm text-slate-500">
                        Revise lotes en estado reservado y confirme la transferencia con evidencia.
                    </p>
                </div>

                <form
                    onSubmit={submitFilters}
                    className="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-[2fr_1fr_auto]"
                >
                    <div className="relative">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            value={form.data.search}
                            onChange={(event) => form.setData('search', event.target.value)}
                            placeholder="Buscar por lote, cliente, DNI o teléfono..."
                            className="w-full rounded-lg border border-slate-200 py-2 pl-9 pr-3"
                        />
                    </div>
                    <select
                        value={form.data.project_id}
                        onChange={(event) => form.setData('project_id', event.target.value)}
                        className="rounded-lg border border-slate-200 px-3 py-2"
                    >
                        <option value="">Todos los proyectos</option>
                        {projects.map((project) => (
                            <option key={project.id} value={project.id}>
                                {project.name}
                            </option>
                        ))}
                    </select>
                    <Button type="submit">Filtrar</Button>
                </form>

                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Unidad</th>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Cliente</th>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Vendedor</th>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Precio</th>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Contrato</th>
                                    <th className="px-4 py-3 text-right font-bold text-slate-600">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {lots.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={6} className="px-4 py-12 text-center text-slate-500">
                                            No se encontraron lotes reservados pendientes de transferencia.
                                        </td>
                                    </tr>
                                ) : (
                                    lots.data.map((lot) => (
                                        <tr key={lot.id}>
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-slate-800">
                                                    {lot.project?.name ?? '-'}
                                                </div>
                                                <div className="text-xs text-slate-500">
                                                    {lot.block}-{lot.number}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="font-medium text-slate-800">
                                                    {lot.client?.name ?? '-'}
                                                </div>
                                                <div className="text-xs text-slate-500">
                                                    DNI: {lot.client?.dni ?? '-'} · Tel: {lot.client?.phone ?? '-'}
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-slate-700">
                                                {lot.advisor?.name ?? '-'}
                                            </td>
                                            <td className="px-4 py-3 font-medium text-slate-700">
                                                {lot.price
                                                    ? `S/ ${Number(lot.price).toLocaleString('es-PE', {
                                                          minimumFractionDigits: 2,
                                                          maximumFractionDigits: 2,
                                                      })}`
                                                    : '-'}
                                            </td>
                                            <td className="px-4 py-3 text-slate-600">
                                                {lot.contract_date
                                                    ? new Date(lot.contract_date).toLocaleDateString('es')
                                                    : '-'}
                                            </td>
                                            <td className="px-4 py-3">
                                                <div className="flex justify-end gap-2">
                                                    <Button type="button" size="sm" variant="outline" asChild>
                                                        <Link href={`/inmopro/lots/${lot.id}`}>
                                                            <Eye className="h-4 w-4" />
                                                        </Link>
                                                    </Button>
                                                    <Button type="button" size="sm" asChild>
                                                        <Link href={`/inmopro/lots/${lot.id}/transfer-confirmation`}>
                                                            Confirmar
                                                        </Link>
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="border-t border-slate-100 px-4 py-3">
                        <Pagination links={lots.links} />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
