import { Head, router } from '@inertiajs/react';
import { Percent, DollarSign, Search, Calendar, CheckCircle2 } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Commission = {
    id: number;
    amount: string;
    percentage: string;
    type: string;
    date: string;
    lot?: { block: string; number: number; project?: { name: string } };
    advisor?: { name: string; level?: { name: string } };
    status?: { code: string; name: string };
};
type CommissionStatus = { id: number; name: string; code: string };

export default function Commissions({
    commissions,
    totalCommissions,
    filters,
}: {
    commissions: { data: Commission[]; links: unknown[] };
    totalCommissions: number;
    filters: { start_date?: string; end_date?: string; search?: string };
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Comisiones', href: '/inmopro/commissions' },
    ];

    const handleFilter = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const form = e.currentTarget;
        const fd = new FormData(form);
        router.get('/inmopro/commissions', {
            start_date: (fd.get('start_date') as string) || undefined,
            end_date: (fd.get('end_date') as string) || undefined,
            search: (fd.get('search') as string) || undefined,
        });
    };

    const markAsPaid = (id: number) => {
        router.post(`/inmopro/commissions/${id}/mark-as-paid`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Comisiones - Inmopro" />
            <div className="space-y-8 p-4 pb-12">
                <div className="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                    <h2 className="mb-2 text-2xl font-black uppercase tracking-tight text-slate-800">
                        Gestión de Comisiones
                    </h2>
                    <p className="mb-8 text-sm font-medium text-slate-500">
                        Cálculo piramidal basado en ventas transferidas.
                    </p>
                    <div className="rounded-2xl border border-emerald-100 bg-emerald-50 p-6">
                        <div className="mb-2 flex items-center justify-between">
                            <p className="text-[10px] font-black uppercase tracking-widest text-emerald-600">
                                Total Comisiones
                            </p>
                            <DollarSign className="h-4 w-4 text-emerald-500" />
                        </div>
                        <p className="text-2xl font-black text-emerald-700">
                            S/ {totalCommissions.toLocaleString()}
                        </p>
                    </div>
                </div>

                <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div className="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/50 p-6 lg:flex-row lg:items-center lg:justify-between">
                        <h3 className="text-sm font-black uppercase tracking-tight text-slate-800">
                            Registro de Liquidación
                        </h3>
                        <form onSubmit={handleFilter} className="grid w-full grid-cols-1 gap-3 sm:grid-cols-3 lg:w-auto">
                            <div className="relative">
                                <Calendar className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 pointer-events-none" />
                                <input
                                    type="date"
                                    name="start_date"
                                    defaultValue={filters.start_date}
                                    className="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-xs font-bold outline-none focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>
                            <div className="relative">
                                <Calendar className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400 pointer-events-none" />
                                <input
                                    type="date"
                                    name="end_date"
                                    defaultValue={filters.end_date}
                                    className="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-3 text-xs font-bold outline-none focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    type="text"
                                    name="search"
                                    placeholder="Buscar vendedor..."
                                    defaultValue={filters.search}
                                    className="w-full rounded-xl border border-slate-200 bg-white py-2 pl-10 pr-4 text-xs font-bold outline-none focus:ring-2 focus:ring-emerald-500"
                                />
                            </div>
                            <button
                                type="submit"
                                className="rounded-xl bg-slate-900 px-4 py-2 font-bold text-white hover:bg-slate-800"
                            >
                                Filtrar
                            </button>
                        </form>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="w-full text-left">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/30">
                                    <th className="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Fecha / Asesor
                                    </th>
                                    <th className="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Monto Lote
                                    </th>
                                    <th className="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Tipo / %
                                    </th>
                                    <th className="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Comisión
                                    </th>
                                    <th className="px-8 py-4 text-right text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Estado
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-50">
                                {commissions.data.map((comm) => (
                                    <tr key={comm.id} className="transition-colors hover:bg-slate-50/80">
                                        <td className="px-8 py-5">
                                            <p className="mb-1 text-[9px] font-black uppercase leading-none text-slate-400">
                                                {comm.date}
                                            </p>
                                            <p className="text-sm font-black uppercase leading-none text-slate-800">
                                                {comm.advisor?.name}
                                            </p>
                                            <span className="text-[9px] font-bold uppercase text-emerald-600">
                                                LOT {comm.lot?.block}-{comm.lot?.number} ({comm.advisor?.level?.name})
                                            </span>
                                        </td>
                                        <td className="px-8 py-5 text-sm font-black text-slate-800">
                                            S/ {Number(comm.amount).toLocaleString()}
                                        </td>
                                        <td className="px-8 py-5">
                                            <span
                                                className={`inline-flex rounded px-2 py-0.5 text-[9px] font-black uppercase ${
                                                    comm.type === 'DIRECTA'
                                                        ? 'bg-blue-100 text-blue-700'
                                                        : 'bg-purple-100 text-purple-700'
                                                }`}
                                            >
                                                {comm.type}
                                            </span>
                                            <span className="ml-1 text-[10px] font-bold text-slate-500">
                                                {comm.percentage}%
                                            </span>
                                        </td>
                                        <td className="px-8 py-5 text-lg font-black text-emerald-600">
                                            S/ {Number(comm.amount).toLocaleString()}
                                        </td>
                                        <td className="px-8 py-5 text-right">
                                            {comm.status?.code === 'PENDIENTE' ? (
                                                <button
                                                    type="button"
                                                    onClick={() => markAsPaid(comm.id)}
                                                    className="inline-flex items-center gap-1.5 rounded-lg border border-amber-100 bg-amber-50 px-2 py-1 text-[9px] font-black uppercase text-amber-600"
                                                >
                                                    <CheckCircle2 className="h-3 w-3" />
                                                    Marcar pagado
                                                </button>
                                            ) : (
                                                <span className="rounded-lg bg-emerald-50 px-2 py-1 text-[9px] font-black uppercase text-emerald-600">
                                                    PAGADO
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {commissions.data.length === 0 && (
                        <div className="flex flex-col items-center justify-center py-24 text-center">
                            <Percent className="mb-4 h-12 w-12 text-slate-300 opacity-10" />
                            <p className="text-sm font-black uppercase tracking-widest text-slate-400">
                                No hay registros para este período
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
