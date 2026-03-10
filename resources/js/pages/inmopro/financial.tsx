import { Head, router } from '@inertiajs/react';
import { DollarSign, Building2, Search, Calendar } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import Pagination, { type PaginationLink } from '@/components/pagination';
import type { BreadcrumbItem } from '@/types';

type Lot = {
    id: number;
    block: string;
    number: number;
    price: string;
    advance?: string;
    remaining_balance?: string;
    contract_date?: string;
    client?: { name: string; dni: string };
    project?: { name: string; location: string };
};
type Project = { id: number; name: string };

export default function Financial({
    lots,
    projects,
    totalValue,
    totalCollected,
    totalPending,
    filters,
}: {
    lots: { data: Lot[]; links: PaginationLink[]; total: number };
    projects: Project[];
    totalValue: number;
    totalCollected: number;
    totalPending: number;
    filters: { project_id?: string; start_date?: string; end_date?: string; search?: string };
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Control Financiero', href: '/inmopro/financial' },
    ];

    const handleFilter = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const form = e.currentTarget;
        const fd = new FormData(form);
        router.get('/inmopro/financial', {
            project_id: (fd.get('project_id') as string) || undefined,
            start_date: (fd.get('start_date') as string) || undefined,
            end_date: (fd.get('end_date') as string) || undefined,
            search: (fd.get('search') as string) || undefined,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Control Financiero - Inmopro" />
            <div className="space-y-8 p-4">
                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
                        <div>
                            <h2 className="text-xl font-black text-slate-800">
                                Control de Caja y Cobranzas
                            </h2>
                            <p className="text-sm font-medium text-slate-500">
                                Seguimiento de ingresos por lotes reservados y transferidos.
                            </p>
                        </div>
                    </div>
                    <form onSubmit={handleFilter} className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <div>
                            <select
                                name="project_id"
                                defaultValue={filters.project_id}
                                className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-xs font-bold text-slate-700 outline-none transition-all focus:ring-2 focus:ring-emerald-500"
                            >
                                <option value="">TODOS LOS PROYECTOS</option>
                                {projects.map((p) => (
                                    <option key={p.id} value={p.id}>
                                        {p.name}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="relative">
                            <Calendar className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                type="date"
                                name="start_date"
                                defaultValue={filters.start_date}
                                className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                        <div className="relative">
                            <Calendar className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                type="date"
                                name="end_date"
                                defaultValue={filters.end_date}
                                className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-xs font-bold text-slate-700 outline-none focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                        <div className="relative">
                            <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                            <input
                                type="text"
                                name="search"
                                placeholder="Buscar cliente..."
                                defaultValue={filters.search}
                                className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 text-xs font-bold outline-none focus:ring-2 focus:ring-emerald-500"
                            />
                        </div>
                        <button
                            type="submit"
                            className="rounded-xl bg-slate-900 px-4 py-2.5 font-bold text-white hover:bg-slate-800"
                        >
                            Filtrar
                        </button>
                    </form>
                </div>

                <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div className="rounded-2xl border border-slate-200 border-l-blue-500 bg-white p-6">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="rounded-lg bg-blue-50 p-2">
                                <Building2 className="h-6 w-6 text-blue-500" />
                            </div>
                            <span className="text-[10px] font-black uppercase tracking-widest text-slate-400">
                                Total Portafolio
                            </span>
                        </div>
                        <p className="text-3xl font-black text-slate-800">
                            S/ {totalValue.toLocaleString()}
                        </p>
                        <p className="mt-2 text-xs font-bold text-blue-600">
                            {lots.total} Ventas registradas
                        </p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 border-l-emerald-500 bg-white p-6">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="rounded-lg bg-emerald-50 p-2">
                                <DollarSign className="h-6 w-6 text-emerald-500" />
                            </div>
                            <span className="rounded-full bg-emerald-100 px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-emerald-700">
                                Cobrado
                            </span>
                        </div>
                        <p className="text-3xl font-black text-emerald-600">
                            S/ {totalCollected.toLocaleString()}
                        </p>
                        <p className="mt-2 text-xs font-bold text-emerald-500">
                            {totalValue > 0
                                ? Math.round((totalCollected / totalValue) * 100)
                                : 0}
                            % de avance
                        </p>
                    </div>
                    <div className="rounded-2xl border border-slate-200 border-l-amber-500 bg-white p-6">
                        <div className="mb-4 flex items-center justify-between">
                            <div className="rounded-lg bg-amber-50 p-2">
                                <DollarSign className="h-6 w-6 text-amber-500" />
                            </div>
                            <span className="text-[10px] font-black uppercase tracking-widest text-slate-400">
                                Por Cobrar
                            </span>
                        </div>
                        <p className="text-3xl font-black text-amber-600">
                            S/ {totalPending.toLocaleString()}
                        </p>
                    </div>
                </div>

                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 p-6">
                        <h3 className="text-lg font-black uppercase tracking-tight text-slate-800">
                            Detalle de Ventas
                        </h3>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50">
                                    <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Fecha / Lote
                                    </th>
                                    <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Cliente / DNI
                                    </th>
                                    <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Proyecto
                                    </th>
                                    <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Monto Total
                                    </th>
                                    <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Abonado
                                    </th>
                                    <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Saldo
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {lots.data.map((lot) => {
                                    const percent =
                                        Number(lot.price) > 0
                                            ? Math.round((Number(lot.advance ?? 0) / Number(lot.price)) * 100)
                                            : 0;
                                    return (
                                        <tr key={lot.id} className="transition-colors hover:bg-slate-50">
                                            <td className="px-6 py-5">
                                                <p className="mb-1 text-xs font-black uppercase leading-none text-slate-800">
                                                    {lot.contract_date ?? '---'}
                                                </p>
                                                <span className="rounded bg-slate-900 px-2 py-0.5 text-[9px] font-black text-white">
                                                    MZ {lot.block}-{lot.number}
                                                </span>
                                            </td>
                                            <td className="px-6 py-5">
                                                <p className="mb-0.5 text-sm font-black text-slate-800">
                                                    {lot.client?.name ?? '-'}
                                                </p>
                                                <p className="text-[10px] font-bold uppercase text-slate-400">
                                                    DNI: {lot.client?.dni ?? 'XXXXXXXX'}
                                                </p>
                                            </td>
                                            <td className="px-6 py-5">
                                                <p className="text-[10px] font-black uppercase text-emerald-600">
                                                    {lot.project?.name}
                                                </p>
                                            </td>
                                            <td className="px-6 py-5 text-sm font-black text-slate-800">
                                                S/ {Number(lot.price).toLocaleString()}
                                            </td>
                                            <td className="px-6 py-5 text-sm font-black text-emerald-600">
                                                S/ {Number(lot.advance ?? 0).toLocaleString()}
                                            </td>
                                            <td className="px-6 py-5 text-sm font-black text-amber-600">
                                                S/ {Number(lot.remaining_balance ?? 0).toLocaleString()}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                    {lots.data.length === 0 ? (
                        <div className="flex flex-col items-center justify-center p-20 text-center">
                            <Search className="mb-4 h-8 w-8 text-slate-300" />
                            <h4 className="font-black uppercase text-slate-800">Sin resultados</h4>
                            <p className="text-sm text-slate-400">
                                No se encontraron ventas para los criterios seleccionados.
                            </p>
                        </div>
                    ) : (
                        <div className="border-t border-slate-100 px-4 py-3">
                            <Pagination links={lots.links} />
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
