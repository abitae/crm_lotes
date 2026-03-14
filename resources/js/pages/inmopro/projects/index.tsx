import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, Download, Eye, FileSpreadsheet, MapPin, Pencil, Plus, Search, Trash2 } from 'lucide-react';
import { useRef, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Pagination, { type PaginationLink } from '@/components/pagination';
import { confirmDelete } from '@/lib/swal';
import type { BreadcrumbItem } from '@/types';

type Project = {
    id: number;
    name: string;
    location?: string | null;
    total_lots?: number | null;
    lots_count?: number;
    free_lots_count?: number;
    reserved_lots_count?: number;
    transferred_lots_count?: number;
    installments_lots_count?: number;
    portfolio_value?: number;
    receivable_balance?: number;
    occupancy_rate?: number;
    consistency_gap?: number;
    is_consistent?: boolean;
    blocks_count?: number;
};

type PageProps = {
    projects: { data: Project[]; links: PaginationLink[]; total?: number };
    filters: { search?: string; location?: string; health?: string; order?: string };
    locations: string[];
    summary: {
        totalProjects: number;
        totalLots: number;
        totalFreeLots: number;
        totalBalance: number;
        inconsistentProjects: number;
    };
};

export default function ProjectsIndex({ projects, filters, locations, summary }: PageProps) {
    const items = projects.data;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Proyectos', href: '/inmopro/projects' },
    ];
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [importing, setImporting] = useState(false);

    const handleDestroy = async (id: number, name: string) => {
        if (await confirmDelete(`Eliminar el proyecto "${name}"?`)) {
            router.delete(`/inmopro/projects/${id}`);
        }
    };

    const handleImportSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const input = fileInputRef.current;

        if (!input?.files?.length) {
            return;
        }

        setImporting(true);
        const formData = new FormData();
        formData.append('file', input.files[0]);

        router.post('/inmopro/projects/import-from-excel', formData, {
            forceFormData: true,
            onFinish: () => {
                setImporting(false);
                input.value = '';
            },
        });
    };

    const handleFilter = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const formData = new FormData(e.currentTarget);

        router.get(
            '/inmopro/projects',
            {
                search: formData.get('search') || undefined,
                location: formData.get('location') || undefined,
                health: formData.get('health') || undefined,
                order: formData.get('order') || undefined,
            },
            { preserveState: true }
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Proyectos - Inmopro" />
            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">Portafolio de proyectos</h1>
                        <p className="mt-1 text-sm text-slate-500">Control de stock, consistencia, cartera y avance comercial por proyecto.</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <a href="/inmopro/projects/excel-template" download="plantilla_proyecto_lotes.xlsx">
                                <Download className="h-4 w-4" />
                                Plantilla Excel
                            </a>
                        </Button>
                        <form onSubmit={handleImportSubmit}>
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept=".xlsx,.xls"
                                className="hidden"
                                onChange={(e) => {
                                    if (e.target.files?.length) {
                                        (e.target.form as HTMLFormElement).requestSubmit();
                                    }
                                }}
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
                                onClick={() => fileInputRef.current?.click()}
                                disabled={importing}
                            >
                                <FileSpreadsheet className="h-4 w-4" />
                                {importing ? 'Importando...' : 'Importar Excel'}
                            </Button>
                        </form>
                        <Button size="sm" asChild>
                            <Link href="/inmopro/projects/create">
                                <Plus className="h-4 w-4" />
                                Nuevo proyecto
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-5">
                    <ProjectMetric label="Proyectos" value={String(summary.totalProjects)} />
                    <ProjectMetric label="Lotes visibles" value={String(summary.totalLots)} tone="emerald" />
                    <ProjectMetric label="Stock libre" value={String(summary.totalFreeLots)} tone="blue" />
                    <ProjectMetric label="Saldo por cobrar" value={`S/ ${summary.totalBalance.toLocaleString('es-PE')}`} tone="amber" />
                    <ProjectMetric label="Inconsistencias" value={String(summary.inconsistentProjects)} tone="rose" />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros de gestion</CardTitle>
                        <CardDescription>Busque por proyecto, sectorice por ubicacion y priorice riesgos de inventario.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleFilter} className="grid gap-3 lg:grid-cols-4">
                            <div className="relative">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input name="search" placeholder="Nombre o ubicacion..." defaultValue={filters.search} className="pl-9" />
                            </div>
                            <select name="location" defaultValue={filters.location ?? ''} className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                <option value="">Todas las ubicaciones</option>
                                {locations.map((location) => (
                                    <option key={location} value={location}>{location}</option>
                                ))}
                            </select>
                            <select name="health" defaultValue={filters.health ?? ''} className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                <option value="">Todos los estados</option>
                                <option value="with_stock">Con stock libre</option>
                                <option value="sold_out">Sin stock libre</option>
                                <option value="inconsistent">Inconsistentes</option>
                            </select>
                            <div className="flex gap-2">
                                <select name="order" defaultValue={filters.order ?? ''} className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm">
                                    <option value="">Orden alfabetico</option>
                                    <option value="lots_desc">Mas lotes</option>
                                    <option value="availability_desc">Mas stock libre</option>
                                    <option value="balance_desc">Mayor saldo por cobrar</option>
                                    <option value="value_desc">Mayor valor de cartera</option>
                                </select>
                                <Button type="submit" variant="secondary">Aplicar</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Tablero del portafolio</CardTitle>
                        <CardDescription>Comparativo de stock, ocupacion y consistencia operativa por proyecto.</CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        {items.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-16 text-center">
                                <div className="rounded-full bg-slate-100 p-4">
                                    <MapPin className="h-10 w-10 text-slate-400" />
                                </div>
                                <p className="mt-4 font-medium text-slate-700">No hay proyectos</p>
                                <p className="mt-1 text-sm text-slate-500">Cree uno manualmente o importe desde Excel.</p>
                                <Button className="mt-4" asChild>
                                    <Link href="/inmopro/projects/create">
                                        <Plus className="h-4 w-4" />
                                        Crear proyecto
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-slate-100 bg-slate-50/80">
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Proyecto</th>
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Stock</th>
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Avance</th>
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Cartera</th>
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Consistencia</th>
                                                <th className="px-4 py-3 text-right font-medium text-slate-600">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-50">
                                            {items.map((project) => (
                                                <tr key={project.id} className="align-top hover:bg-slate-50/50">
                                                    <td className="px-4 py-4">
                                                        <div>
                                                            <p className="font-semibold text-slate-900">{project.name}</p>
                                                            <p className="text-xs text-slate-500">{project.location ?? 'Sin ubicacion'} · {project.blocks_count ?? 0} manzana(s)</p>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-4">
                                                        <div className="space-y-1 text-xs text-slate-600">
                                                            <p>Planificado: <span className="font-semibold text-slate-900">{project.total_lots ?? 0}</span></p>
                                                            <p>Real: <span className="font-semibold text-slate-900">{project.lots_count ?? 0}</span></p>
                                                            <p>Libres: <span className="font-semibold text-emerald-700">{project.free_lots_count ?? 0}</span></p>
                                                            <p>Reservados/colocados: <span className="font-semibold text-slate-900">{(project.reserved_lots_count ?? 0) + (project.transferred_lots_count ?? 0) + (project.installments_lots_count ?? 0)}</span></p>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-4">
                                                        <div className="space-y-2">
                                                            <p className="text-sm font-semibold text-slate-900">{project.occupancy_rate ?? 0}% ocupado</p>
                                                            <div className="h-2 w-40 rounded-full bg-slate-100">
                                                                <div className="h-2 rounded-full bg-emerald-500" style={{ width: `${Math.min(project.occupancy_rate ?? 0, 100)}%` }} />
                                                            </div>
                                                            <div className="flex flex-wrap gap-1 text-[11px]">
                                                                <span className="rounded-full bg-amber-100 px-2 py-1 font-semibold text-amber-700">Reservado: {project.reserved_lots_count ?? 0}</span>
                                                                <span className="rounded-full bg-slate-200 px-2 py-1 font-semibold text-slate-700">Transferido: {project.transferred_lots_count ?? 0}</span>
                                                                <span className="rounded-full bg-indigo-100 px-2 py-1 font-semibold text-indigo-700">Cuotas: {project.installments_lots_count ?? 0}</span>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-4">
                                                        <div className="space-y-1 text-xs text-slate-600">
                                                            <p>Valor cartera: <span className="font-semibold text-slate-900">S/ {(project.portfolio_value ?? 0).toLocaleString('es-PE')}</span></p>
                                                            <p>Saldo pendiente: <span className="font-semibold text-amber-700">S/ {(project.receivable_balance ?? 0).toLocaleString('es-PE')}</span></p>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-4">
                                                        {project.is_consistent ? (
                                                            <span className="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-bold text-emerald-700">
                                                                Consistente
                                                            </span>
                                                        ) : (
                                                            <div className="space-y-1">
                                                                <span className="inline-flex items-center gap-1 rounded-full bg-rose-100 px-2.5 py-1 text-xs font-bold text-rose-700">
                                                                    <AlertTriangle className="h-3.5 w-3.5" />
                                                                    Inconsistente
                                                                </span>
                                                                <p className="text-xs text-slate-500">Brecha: {project.consistency_gap ?? 0}</p>
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-4">
                                                        <div className="flex justify-end gap-1">
                                                            <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                                                                <Link href={`/inmopro/projects/${project.id}`} title="Ver">
                                                                    <Eye className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                            <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                                                                <Link href={`/inmopro/projects/${project.id}/edit`} title="Editar">
                                                                    <Pencil className="h-4 w-4" />
                                                                </Link>
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="icon"
                                                                className="h-8 w-8 text-slate-500 hover:bg-red-50 hover:text-red-600"
                                                                onClick={() => handleDestroy(project.id, project.name)}
                                                                title="Eliminar"
                                                            >
                                                                <Trash2 className="h-4 w-4" />
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <div className="border-t border-slate-100 px-4 py-3">
                                    <Pagination links={projects.links} />
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

function ProjectMetric({
    label,
    value,
    tone = 'slate',
}: {
    label: string;
    value: string;
    tone?: 'slate' | 'emerald' | 'blue' | 'amber' | 'rose';
}) {
    const tones = {
        slate: 'text-slate-900',
        emerald: 'text-emerald-600',
        blue: 'text-blue-600',
        amber: 'text-amber-600',
        rose: 'text-rose-600',
    };

    return (
        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-[10px] font-black uppercase tracking-widest text-slate-400">{label}</p>
            <p className={`mt-3 text-2xl font-black ${tones[tone]}`}>{value}</p>
        </div>
    );
}
