import { Head, router } from '@inertiajs/react';
import type { ComponentType, FormEvent } from 'react';
import { BarChart3, FileDown, FolderKanban, Target, TrendingUp, Users, Wallet } from 'lucide-react';
import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import AppLayout from '@/layouts/app-layout';
import { formatDate } from '@/lib/date';
import type { BreadcrumbItem } from '@/types';

type Project = { id: number; name: string };
type Team = { id: number; name: string; color?: string | null };
type Advisor = { id: number; name: string; team?: Team | null };
type ReportRow = {
    id: number;
    label: string;
    sold_amount: number;
    goal_amount: number;
    collected_amount: number;
    pending_amount: number;
    lots_count: number;
    pct: number;
    color?: string | null;
    team_name?: string | null;
};
type Filters = {
    view: 'projects' | 'teams' | 'advisors';
    project_id?: number | null;
    team_id?: number | null;
    advisor_id?: number | null;
    start_date?: string | null;
    end_date?: string | null;
};
type Summary = {
    sold_amount: number;
    goal_amount: number;
    collected_amount: number;
    pending_amount: number;
    lots_count: number;
    entities_count: number;
    pct: number;
};
type FilterLabels = {
    project?: string | null;
    team?: string | null;
    advisor?: string | null;
    start_date?: string | null;
    end_date?: string | null;
};

export default function Reports({
    view,
    viewLabel,
    filters,
    summary,
    rows,
    projects,
    teams,
    advisors,
    generatedAt,
    filterLabels,
}: {
    view: Filters['view'];
    viewLabel: string;
    filters: Filters;
    summary: Summary;
    rows: ReportRow[];
    projects: Project[];
    teams: Team[];
    advisors: Advisor[];
    generatedAt: string;
    filterLabels: FilterLabels;
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Reportes', href: '/inmopro/reports' },
    ];

    const topRows = rows.slice(0, 12).map((row) => ({
        name: row.label,
        Ventas: row.sold_amount,
        Meta: row.goal_amount,
        pct: row.pct,
        color: row.color ?? '#0f172a',
    }));

    const filterSummary = [
        filterLabels.project ? `Proyecto: ${filterLabels.project}` : null,
        filterLabels.team ? `Team: ${filterLabels.team}` : null,
        filterLabels.advisor ? `Vendedor: ${filterLabels.advisor}` : null,
        filterLabels.start_date ? `Desde: ${filterLabels.start_date}` : null,
        filterLabels.end_date ? `Hasta: ${filterLabels.end_date}` : null,
    ]
        .filter(Boolean)
        .join(' · ');

    const pdfUrl = () => {
        const params = new URLSearchParams();

        params.set('view', view);

        if (filters.project_id) {
            params.set('project_id', String(filters.project_id));
        }

        if (filters.team_id) {
            params.set('team_id', String(filters.team_id));
        }

        if (filters.advisor_id) {
            params.set('advisor_id', String(filters.advisor_id));
        }

        if (filters.start_date) {
            params.set('start_date', filters.start_date);
        }

        if (filters.end_date) {
            params.set('end_date', filters.end_date);
        }

        return `/inmopro/reports/pdf?${params.toString()}`;
    };

    const handleFilter = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);

        router.get('/inmopro/reports', {
            view: (formData.get('view') as Filters['view']) || 'projects',
            project_id: (formData.get('project_id') as string) || undefined,
            team_id: (formData.get('team_id') as string) || undefined,
            advisor_id: (formData.get('advisor_id') as string) || undefined,
            start_date: (formData.get('start_date') as string) || undefined,
            end_date: (formData.get('end_date') as string) || undefined,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reportes - Inmopro" />
            <div className="space-y-8 p-4 md:p-6">
                <section className="rounded-[2rem] bg-slate-950 p-8 text-white shadow-2xl">
                    <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div className="space-y-3">
                            <p className="text-xs font-black uppercase tracking-[0.3em] text-emerald-300">
                                Inteligencia comercial
                            </p>
                            <h1 className="text-3xl font-black tracking-tight">Reporte por {viewLabel.toLowerCase()}</h1>
                            <p className="max-w-2xl text-sm text-slate-300">
                                Ventas, metas, cobranza y saldo pendiente consolidados por eje comercial.
                            </p>
                            <p className="text-xs font-semibold text-slate-400">
                                {filterSummary || 'Sin filtros analíticos aplicados'} · Generado el {generatedAt}
                            </p>
                        </div>
                        <a
                            href={pdfUrl()}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center gap-2 rounded-2xl border border-white/10 bg-white/10 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-white/15"
                        >
                            <FileDown className="h-4 w-4" />
                            Ver PDF / Imprimir
                        </a>
                    </div>
                </section>

                <form
                    onSubmit={handleFilter}
                    className="grid gap-3 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-3 xl:grid-cols-6"
                >
                    <select
                        name="view"
                        defaultValue={view}
                        className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700 outline-none"
                    >
                        <option value="projects">Proyecto</option>
                        <option value="teams">Team</option>
                        <option value="advisors">Vendedor</option>
                    </select>
                    <select
                        name="project_id"
                        defaultValue={filters.project_id ?? ''}
                        className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700 outline-none"
                    >
                        <option value="">Todos los proyectos</option>
                        {projects.map((project) => (
                            <option key={project.id} value={project.id}>
                                {project.name}
                            </option>
                        ))}
                    </select>
                    <select
                        name="team_id"
                        defaultValue={filters.team_id ?? ''}
                        className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700 outline-none"
                    >
                        <option value="">Todos los teams</option>
                        {teams.map((team) => (
                            <option key={team.id} value={team.id}>
                                {team.name}
                            </option>
                        ))}
                    </select>
                    <select
                        name="advisor_id"
                        defaultValue={filters.advisor_id ?? ''}
                        className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700 outline-none"
                    >
                        <option value="">Todos los vendedores</option>
                        {advisors.map((advisor) => (
                            <option key={advisor.id} value={advisor.id}>
                                {advisor.name}
                            </option>
                        ))}
                    </select>
                    <input
                        type="date"
                        name="start_date"
                        defaultValue={filters.start_date ?? ''}
                        className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700 outline-none"
                    />
                    <div className="flex gap-3">
                        <input
                            type="date"
                            name="end_date"
                            defaultValue={filters.end_date ?? ''}
                            className="min-w-0 flex-1 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700 outline-none"
                        />
                        <button
                            type="submit"
                            className="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-800"
                        >
                            Filtrar
                        </button>
                    </div>
                </form>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
                    <MetricCard label="Ventas" value={summary.sold_amount} icon={TrendingUp} tone="emerald" />
                    <MetricCard label="Meta" value={summary.goal_amount} icon={Target} tone="sky" />
                    <MetricCard label="Cobrado" value={summary.collected_amount} icon={Wallet} tone="slate" />
                    <MetricCard label="Pendiente" value={summary.pending_amount} icon={BarChart3} tone="amber" />
                    <MetricCard label={`Total ${viewLabel}`} value={summary.entities_count} icon={Users} tone="rose" raw />
                </div>

                <div className="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
                    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <div className="mb-4 flex items-center gap-2">
                            <FolderKanban className="h-5 w-5 text-slate-500" />
                            <h2 className="text-lg font-black text-slate-900">Ranking comercial</h2>
                        </div>
                        {topRows.length === 0 ? (
                            <EmptyState />
                        ) : (
                            <div className="h-[360px]">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={topRows}>
                                        <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="#e2e8f0" />
                                        <XAxis dataKey="name" axisLine={false} tickLine={false} tick={{ fontSize: 11 }} />
                                        <YAxis axisLine={false} tickLine={false} tick={{ fontSize: 11 }} />
                                        <Tooltip
                                            cursor={{ fill: '#f8fafc' }}
                                            contentStyle={{ borderRadius: '16px', border: '1px solid #e2e8f0' }}
                                        />
                                        <Bar dataKey="Ventas" radius={[8, 8, 0, 0]}>
                                            {topRows.map((entry) => (
                                                <Cell key={entry.name} fill={entry.pct >= 100 ? '#10b981' : entry.color ?? '#0f172a'} />
                                            ))}
                                        </Bar>
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        )}
                    </div>

                    <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                        <h2 className="text-lg font-black text-slate-900">Lectura rápida</h2>
                        <div className="mt-5 space-y-4 text-sm text-slate-600">
                            <Insight label="Cumplimiento global" value={`${summary.pct}%`} />
                            <Insight label="Entidades analizadas" value={String(summary.entities_count)} />
                            <Insight label="Lotes considerados" value={String(summary.lots_count)} />
                            <Insight label="Rango aplicado" value={rangeLabel(filters.start_date, filters.end_date)} />
                        </div>
                    </div>
                </div>

                <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div className="border-b border-slate-100 px-6 py-4">
                        <h2 className="text-lg font-black text-slate-900">Detalle por {viewLabel.toLowerCase()}</h2>
                        <p className="mt-1 text-sm text-slate-500">
                            Comparativo de ventas, meta, cobranza y saldo pendiente.
                        </p>
                    </div>

                    {rows.length === 0 ? (
                        <div className="px-6 py-14">
                            <EmptyState />
                        </div>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="w-full min-w-[1100px] text-left text-sm">
                                <thead className="bg-slate-50">
                                    <tr>
                                        <th className="px-6 py-3 font-bold text-slate-500">{viewLabel.slice(0, -1) || viewLabel}</th>
                                        <th className="px-6 py-3 font-bold text-slate-500">Ventas</th>
                                        <th className="px-6 py-3 font-bold text-slate-500">Meta</th>
                                        <th className="px-6 py-3 font-bold text-slate-500">% cumplimiento</th>
                                        <th className="px-6 py-3 font-bold text-slate-500">Cobrado</th>
                                        <th className="px-6 py-3 font-bold text-slate-500">Pendiente</th>
                                        <th className="px-6 py-3 font-bold text-slate-500">Lotes</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-slate-100">
                                    {rows.map((row) => (
                                        <tr key={`${view}-${row.id}`} className="hover:bg-slate-50/70">
                                            <td className="px-6 py-4">
                                                <p className="font-black text-slate-900">{row.label}</p>
                                                {row.team_name && (
                                                    <p className="text-xs font-semibold text-slate-500">{row.team_name}</p>
                                                )}
                                            </td>
                                            <td className="px-6 py-4 font-semibold text-slate-800">
                                                S/ {row.sold_amount.toLocaleString()}
                                            </td>
                                            <td className="px-6 py-4 font-semibold text-slate-600">
                                                S/ {row.goal_amount.toLocaleString()}
                                            </td>
                                            <td className="px-6 py-4">
                                                <span className={badgeTone(row.pct)}>
                                                    {row.pct}%
                                                </span>
                                            </td>
                                            <td className="px-6 py-4 font-semibold text-emerald-600">
                                                S/ {row.collected_amount.toLocaleString()}
                                            </td>
                                            <td className="px-6 py-4 font-semibold text-amber-600">
                                                S/ {row.pending_amount.toLocaleString()}
                                            </td>
                                            <td className="px-6 py-4 text-slate-600">{row.lots_count}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}

function MetricCard({
    label,
    value,
    icon: Icon,
    tone,
    raw = false,
}: {
    label: string;
    value: number;
    icon: ComponentType<{ className?: string }>;
    tone: 'emerald' | 'sky' | 'slate' | 'amber' | 'rose';
    raw?: boolean;
}) {
    const styles = {
        emerald: 'bg-emerald-50 text-emerald-700',
        sky: 'bg-sky-50 text-sky-700',
        slate: 'bg-slate-100 text-slate-700',
        amber: 'bg-amber-50 text-amber-700',
        rose: 'bg-rose-50 text-rose-700',
    };

    return (
        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <div className="flex items-start justify-between">
                <div>
                    <p className="text-xs font-bold uppercase tracking-wide text-slate-500">{label}</p>
                    <p className="mt-3 text-2xl font-black text-slate-900">
                        {raw ? value.toLocaleString() : `S/ ${value.toLocaleString()}`}
                    </p>
                </div>
                <div className={`rounded-2xl p-3 ${styles[tone]}`}>
                    <Icon className="h-5 w-5" />
                </div>
            </div>
        </div>
    );
}

function Insight({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-2xl border border-slate-100 bg-slate-50 px-4 py-3">
            <p className="text-xs font-bold uppercase tracking-wide text-slate-500">{label}</p>
            <p className="mt-1 font-black text-slate-900">{value}</p>
        </div>
    );
}

function EmptyState() {
    return (
        <div className="flex min-h-40 items-center justify-center rounded-3xl border border-dashed border-slate-200 bg-slate-50 text-center">
            <div>
                <p className="font-bold text-slate-700">No hay datos para este filtro.</p>
                <p className="mt-1 text-sm text-slate-500">Ajusta proyecto, team, vendedor o rango de fechas.</p>
            </div>
        </div>
    );
}

function rangeLabel(startDate?: string | null, endDate?: string | null): string {
    if (!startDate && !endDate) {
        return 'Todo el periodo';
    }

    if (startDate && endDate) {
        return `${formatDate(startDate)} - ${formatDate(endDate)}`;
    }

    if (startDate) {
        return `Desde ${formatDate(startDate)}`;
    }

    return `Hasta ${formatDate(endDate)}`;
}

function badgeTone(pct: number): string {
    if (pct >= 100) {
        return 'rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700';
    }

    if (pct >= 60) {
        return 'rounded-full bg-sky-100 px-2.5 py-1 text-xs font-bold text-sky-700';
    }

    return 'rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-700';
}
