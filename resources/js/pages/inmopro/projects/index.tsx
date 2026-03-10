import { Head, Link, router } from '@inertiajs/react';
import { MapPin, Plus, Eye, Pencil, Trash2, FileSpreadsheet, Download } from 'lucide-react';
import { useRef, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Pagination, { type PaginationLink } from '@/components/pagination';
import { confirmDelete } from '@/lib/swal';
import type { BreadcrumbItem } from '@/types';

type Project = {
    id: number;
    name: string;
    location?: string;
    total_lots?: number;
    blocks?: string[];
    lots_count?: number;
};

type PageProps = { projects: { data: Project[]; links: PaginationLink[] } };

export default function ProjectsIndex({ projects }: PageProps) {
    const items = projects.data;
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Proyectos', href: '/inmopro/projects' },
    ];
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [importing, setImporting] = useState(false);

    const handleDestroy = async (id: number, name: string) => {
        if (await confirmDelete(`¿Eliminar el proyecto "${name}"?`)) {
            router.delete(`/inmopro/projects/${id}`);
        }
    };

    const handleImportSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const input = fileInputRef.current;
        if (!input?.files?.length) return;
        setImporting(true);
        const formData = new FormData();
        formData.append('file', input.files[0]);
        router.post('/inmopro/projects/import-from-excel', formData, {
            forceFormData: true,
            onFinish: () => { setImporting(false); input.value = ''; },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Proyectos - Inmopro" />
            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">Proyectos</h1>
                        <p className="mt-1 text-sm text-slate-500">Administración de proyectos y manzanas.</p>
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
                                    if (e.target.files?.length) (e.target.form as HTMLFormElement).requestSubmit();
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
                                {importing ? 'Importando…' : 'Importar Excel'}
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

                <Card>
                    <CardHeader>
                        <CardTitle>Listado de proyectos</CardTitle>
                        <CardDescription>Proyectos registrados y cantidad de lotes.</CardDescription>
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
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Nombre</th>
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Ubicación</th>
                                                <th className="px-4 py-3 text-right font-medium text-slate-600">Lotes</th>
                                                <th className="px-4 py-3 text-right font-medium text-slate-600">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-50">
                                            {items.map((project) => (
                                                <tr key={project.id} className="hover:bg-slate-50/50">
                                                    <td className="px-4 py-3 font-medium text-slate-900">{project.name}</td>
                                                    <td className="px-4 py-3 text-slate-600">{project.location ?? '—'}</td>
                                                    <td className="px-4 py-3 text-right tabular-nums text-slate-600">{project.lots_count ?? 0}</td>
                                                    <td className="px-4 py-3">
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
