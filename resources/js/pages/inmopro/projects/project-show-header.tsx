import { Link } from '@inertiajs/react';
import { LayoutGrid, MapPin, Pencil, Plus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import type { Project } from './show-types';

export function ProjectShowHeader({
    project,
    clientError,
}: {
    project: Project;
    clientError?: string;
}) {
    return (
        <>
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-slate-900">{project.name}</h1>
                    {project.location && (
                        <p className="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                            <MapPin className="h-4 w-4 text-slate-400" />
                            {project.location}
                        </p>
                    )}
                </div>
                <div className="flex flex-wrap gap-2">
                    <Button variant="outline" size="sm" asChild>
                        <Link href={`/inmopro/lots?project_id=${project.id}`}>
                            <LayoutGrid className="h-4 w-4" />
                            Ver inventario
                        </Link>
                    </Button>
                    <Button size="sm" asChild>
                        <Link href={`/inmopro/lots/create?project_id=${project.id}`}>
                            <Plus className="h-4 w-4" />
                            Nuevo lote
                        </Link>
                    </Button>
                    <Button size="sm" asChild>
                        <Link href={`/inmopro/projects/${project.id}/edit`}>
                            <Pencil className="h-4 w-4" />
                            Editar proyecto
                        </Link>
                    </Button>
                </div>
            </div>

            <div className="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                <span className="tabular-nums font-medium text-slate-700">{project.lots?.length ?? 0} lotes</span>
                <span>·</span>
                <span>Manzanas: {project.blocks?.length ? project.blocks.join(', ') : '-'}</span>
            </div>

            {clientError && (
                <Alert variant="destructive">
                    <AlertDescription>{clientError}</AlertDescription>
                </Alert>
            )}
        </>
    );
}
