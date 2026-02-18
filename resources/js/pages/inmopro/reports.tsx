import { Head, router } from '@inertiajs/react';
import { Target, TrendingUp, Flag, Trophy, AlertTriangle } from 'lucide-react';
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    Cell,
} from 'recharts';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type AdvisorLevel = { id: number; name: string };
type SellerPerformance = {
    id: number;
    name: string;
    full: string;
    Logrado: number;
    Meta: number;
    pct: number;
};

export default function Reports({
    advisorLevels,
    selectedLevelId,
    globalSold,
    globalGoal,
    globalPct,
    levelSold,
    levelGoal,
    levelPct,
    levelAdvisorsCount,
    sellersPerformance,
}: {
    advisorLevels: AdvisorLevel[];
    selectedLevelId: number;
    globalSold: number;
    globalGoal: number;
    globalPct: number;
    levelSold: number;
    levelGoal: number;
    levelPct: number;
    levelAdvisorsCount: number;
    sellersPerformance: SellerPerformance[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Reportes', href: '/inmopro/reports' },
    ];

    const levelColors: Record<number, string> = {
        1: '#3b82f6',
        2: '#10b981',
        3: '#6366f1',
        4: '#a855f7',
    };
    const themeColor = levelColors[advisorLevels.findIndex((l) => l.id === selectedLevelId) + 1] ?? '#3b82f6';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reportes - Inmopro" />
            <div className="space-y-8 p-4 pb-12">
                <div className="flex flex-col gap-6 xl:flex-row">
                    <div className="relative flex-1 overflow-hidden rounded-3xl bg-slate-900 p-8 text-white shadow-2xl">
                        <div className="relative z-10 flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h2 className="mb-2 text-3xl font-black uppercase tracking-tighter">
                                    Monitor General
                                </h2>
                                <p className="flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-slate-400">
                                    <Flag className="h-4 w-4 text-emerald-500" />
                                    Meta Corporativa Total
                                </p>
                            </div>
                            <div className="text-right">
                                <p className="text-4xl font-black leading-none text-emerald-400">
                                    S/ {globalSold.toLocaleString()}
                                </p>
                                <p className="mt-1 text-[10px] font-bold uppercase text-slate-500">
                                    Meta: S/ {globalGoal.toLocaleString()}
                                </p>
                            </div>
                        </div>
                        <div className="mt-8">
                            <div className="mb-2 flex items-end justify-between">
                                <span className="text-[10px] font-black uppercase text-slate-500">
                                    Progreso Total
                                </span>
                                <span className="text-xl font-black text-white">{globalPct}%</span>
                            </div>
                            <div className="h-3 w-full overflow-hidden rounded-full bg-slate-800">
                                <div
                                    className="h-full rounded-full bg-gradient-to-r from-emerald-600 to-emerald-400 transition-all duration-1000"
                                    style={{ width: `${globalPct}%` }}
                                />
                            </div>
                        </div>
                    </div>
                    <div className="flex min-w-[280px] flex-col justify-center rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p className="mb-4 px-2 text-[10px] font-black uppercase tracking-widest text-slate-400">
                            Explorar Niveles
                        </p>
                        <div className="flex max-h-[300px] flex-col gap-2 overflow-y-auto pr-2">
                            {advisorLevels.map((level) => (
                                <button
                                    key={level.id}
                                    type="button"
                                    onClick={() =>
                                        router.get('/inmopro/reports', { level_id: level.id })
                                    }
                                    className={`flex items-center justify-between rounded-2xl p-3 transition-all ${
                                        selectedLevelId === level.id
                                            ? 'ring-2 ring-current bg-slate-100 text-slate-800'
                                            : 'bg-slate-50 text-slate-400 hover:bg-slate-100'
                                    }`}
                                >
                                    <span className="font-black text-[11px] uppercase">
                                        {level.name}
                                    </span>
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                <div className="space-y-6">
                    <div className="flex items-center gap-3">
                        <div
                            className="h-8 w-1.5 rounded-full"
                            style={{ backgroundColor: themeColor }}
                        />
                        <h3 className="text-xl font-black uppercase tracking-tight text-slate-800">
                            Reporte por Nivel
                        </h3>
                        <span className="text-xs font-bold text-slate-400">
                            ({levelAdvisorsCount} Vendedores)
                        </span>
                    </div>

                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
                        <div className="relative overflow-hidden rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
                            <div className="relative z-10">
                                <div className="mb-8 flex items-center justify-between">
                                    <div
                                        className="rounded-2xl p-3"
                                        style={{ backgroundColor: `${themeColor}20` }}
                                    >
                                        <Target
                                            className="h-6 w-6"
                                            style={{ color: themeColor }}
                                        />
                                    </div>
                                    <span
                                        className="text-2xl font-black"
                                        style={{ color: themeColor }}
                                    >
                                        {levelPct}%
                                    </span>
                                </div>
                                <p className="mb-1 text-sm font-bold uppercase text-slate-400">
                                    Logro Acumulado
                                </p>
                                <h4 className="mb-2 text-3xl font-black leading-none text-slate-800">
                                    S/ {levelSold.toLocaleString()}
                                </h4>
                                <p className="text-[10px] font-bold uppercase text-slate-400">
                                    Meta del Nivel: S/ {levelGoal.toLocaleString()}
                                </p>
                            </div>
                        </div>
                        <div className="h-[250px] rounded-3xl border border-slate-200 bg-white p-8 shadow-sm lg:col-span-2">
                            <h4 className="mb-8 flex items-center gap-2 text-sm font-black uppercase text-slate-800">
                                <TrendingUp className="h-4 w-4 text-emerald-500" />
                                Ranking de Productividad
                            </h4>
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={sellersPerformance.slice(0, 15)}>
                                    <CartesianGrid
                                        strokeDasharray="3 3"
                                        vertical={false}
                                        stroke="#f1f5f9"
                                    />
                                    <XAxis
                                        dataKey="name"
                                        axisLine={false}
                                        tickLine={false}
                                        tick={{ fontSize: 9, fontWeight: 800, fill: '#64748b' }}
                                    />
                                    <YAxis hide />
                                    <Tooltip
                                        cursor={{ fill: '#f8fafc' }}
                                        contentStyle={{
                                            borderRadius: '16px',
                                            border: 'none',
                                            boxShadow: '0 20px 25px -5px rgb(0 0 0 / 0.1)',
                                            fontSize: '11px',
                                            fontWeight: 'bold',
                                        }}
                                    />
                                    <Bar dataKey="Logrado" radius={[6, 6, 0, 0]} barSize={25}>
                                        {sellersPerformance.slice(0, 15).map((entry, index) => (
                                            <Cell
                                                key={`cell-${index}`}
                                                fill={
                                                    entry.pct >= 100
                                                        ? '#10b981'
                                                        : entry.pct >= 50
                                                          ? themeColor
                                                          : '#f43f5e'
                                                }
                                            />
                                        ))}
                                    </Bar>
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                        <div className="flex items-center justify-between border-b border-slate-100 bg-slate-50/50 p-6">
                            <h4 className="text-[10px] font-black uppercase tracking-widest text-slate-500">
                                Listado de Rendimiento
                            </h4>
                        </div>
                        <div className="max-h-[400px] overflow-y-auto">
                            <table className="w-full text-left">
                                <thead className="sticky top-0 z-10 border-b border-slate-100 bg-white shadow-sm">
                                    <tr>
                                        <th className="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                            Asesor
                                        </th>
                                        <th className="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                            Venta
                                        </th>
                                        <th className="px-8 py-4 text-[10px] font-black uppercase tracking-widest text-slate-400">
                                            Meta
                                        </th>
                                        <th className="px-8 py-4 text-right text-[10px] font-black uppercase tracking-widest text-slate-400">
                                            Estado
                                        </th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-50">
                                    {sellersPerformance.map((seller, idx) => (
                                        <tr key={seller.id} className="transition-colors hover:bg-slate-50">
                                            <td className="px-8 py-4">
                                                <p className="text-sm font-black uppercase text-slate-800">
                                                    {seller.full}
                                                </p>
                                                <p className="text-[9px] font-bold uppercase tracking-tighter text-slate-400">
                                                    Posición #{idx + 1}
                                                </p>
                                            </td>
                                            <td className="px-8 py-4 text-sm font-black text-slate-700">
                                                S/ {seller.Logrado.toLocaleString()}
                                            </td>
                                            <td className="px-8 py-4">
                                                <div className="flex max-w-[100px] items-center gap-3">
                                                    <div className="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-100">
                                                        <div
                                                            className="h-full rounded-full transition-all duration-1000"
                                                            style={{
                                                                width: `${Math.min(seller.pct, 100)}%`,
                                                                backgroundColor:
                                                                    seller.pct >= 100
                                                                        ? '#10b981'
                                                                        : seller.pct >= 50
                                                                          ? themeColor
                                                                          : '#ef4444',
                                                            }}
                                                        />
                                                    </div>
                                                    <span className="text-xs font-black text-slate-600">
                                                        {seller.pct}%
                                                    </span>
                                                </div>
                                            </td>
                                            <td className="px-8 py-4 text-right">
                                                {seller.pct >= 100 ? (
                                                    <Trophy className="inline h-5 w-5 text-amber-400" />
                                                ) : seller.pct < 30 ? (
                                                    <AlertTriangle className="inline h-5 w-5 text-red-400" />
                                                ) : (
                                                    <TrendingUp className="inline h-5 w-5 text-slate-300" />
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
