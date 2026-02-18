import { Head, Link, router } from '@inertiajs/react';
import { Search, UserPlus, ChevronRight, Phone } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Advisor = {
    id: number;
    name: string;
    email: string;
    phone: string;
    personal_quota: string;
    lots_count?: number;
    level?: { name: string; color?: string };
    superior?: { name: string };
};

export default function AdvisorsIndex({
    advisors,
    filters,
}: {
    advisors: { data: Advisor[]; links: unknown[] };
    filters: { search?: string };
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Vendedores', href: '/inmopro/advisors' },
    ];

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        const form = e.target as HTMLFormElement;
        const q = new FormData(form).get('search') as string;
        router.get('/inmopro/advisors', { search: q || undefined }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Vendedores - Inmopro" />
            <div className="space-y-6 p-4">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 className="text-2xl font-black uppercase tracking-tight text-slate-800">
                            Estructura Comercial
                        </h2>
                        <p className="text-sm italic font-medium text-slate-500">
                            Gestión de jerarquías y reporte directo.
                        </p>
                    </div>
                    <Link
                        href="/inmopro/advisors/create"
                        className="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 py-2.5 font-black text-white shadow-xl shadow-slate-200 transition-all hover:bg-slate-800 active:scale-95 sm:w-auto"
                    >
                        <UserPlus className="h-5 w-5" />
                        NUEVO ASESOR
                    </Link>
                </div>

                <form
                    onSubmit={handleSearch}
                    className="flex gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <div className="relative w-full sm:w-80">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <input
                            name="search"
                            type="text"
                            placeholder="Buscar por nombre o email..."
                            className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 font-medium outline-none transition-all focus:ring-2 focus:ring-emerald-500"
                            defaultValue={filters.search}
                        />
                    </div>
                    <button
                        type="submit"
                        className="rounded-xl bg-slate-900 px-4 py-2.5 font-bold text-white hover:bg-slate-800"
                    >
                        Buscar
                    </button>
                </form>

                <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full border-collapse text-left">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/50">
                                    <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Nivel / Vendedor
                                    </th>
                                    <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Superior
                                    </th>
                                    <th className="px-6 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Cuota
                                    </th>
                                    <th className="px-6 py-4 text-right text-[10px] font-black uppercase tracking-widest text-slate-400">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {advisors.data.map((adv) => (
                                    <tr key={adv.id} className="transition-colors hover:bg-slate-50">
                                        <td className="px-6 py-5">
                                            <div className="flex items-center gap-3">
                                                <span className="rounded-lg bg-slate-900 px-2.5 py-1 text-[10px] font-black text-white">
                                                    {adv.level?.name ?? '-'}
                                                </span>
                                                <div>
                                                    <p className="text-sm font-black leading-none text-slate-800">
                                                        {adv.name}
                                                    </p>
                                                    <p className="mt-1 text-[9px] font-medium uppercase text-slate-400">
                                                        {adv.email}
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-6 py-5">
                                            <p className="text-xs font-bold text-slate-700">
                                                {adv.superior?.name ?? 'Alta Gerencia'}
                                            </p>
                                        </td>
                                        <td className="px-6 py-5">
                                            <span className="text-xs font-bold text-slate-700">
                                                S/ {Number(adv.personal_quota).toLocaleString()}
                                            </span>
                                        </td>
                                        <td className="px-6 py-5 text-right">
                                            <Link
                                                href={`/inmopro/advisors/${adv.id}`}
                                                className="inline-flex p-2 text-slate-400 transition-all hover:rounded-lg hover:bg-slate-100 hover:text-slate-900"
                                            >
                                                <ChevronRight className="h-5 w-5" />
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
