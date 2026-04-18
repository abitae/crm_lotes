import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';

type ProjectAsset = {
    id: number;
    kind: 'image' | 'document';
    title?: string | null;
    file_name: string;
    download_url: string;
};
type Project = {
    id: number;
    name: string;
    location?: string;
    total_lots?: number;
    blocks?: string[];
    assets?: ProjectAsset[];
};
type ProjectEditForm = {
    name: string;
    location: string;
    total_lots: number | '';
    blocks: string[];
    image_files: File[];
    document_files: File[];
    _method?: 'put';
};

export default function ProjectsEdit({ project }: { project: Project }) {
    const blocks = project.blocks ?? [];
    const [blockInput, setBlockInput] = useState('');
    const [blocksList, setBlocksList] = useState<string[]>(blocks);
    const { data, setData, post, processing, errors, transform } = useForm<ProjectEditForm>({
            name: project.name,
            location: project.location ?? '',
            total_lots: project.total_lots ?? ('' as number | ''),
            blocks: blocksList,
            image_files: [],
            document_files: [],
        });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Proyectos', href: '/inmopro/projects' },
        { title: 'Editar', href: `/inmopro/projects/${project.id}/edit` },
    ];

    const addBlock = () => {
        const v = blockInput.trim().toUpperCase();
        if (v && !blocksList.includes(v)) {
            const next = [...blocksList, v];
            setBlocksList(next);
            setData('blocks', next);
            setBlockInput('');
        }
    };

    const removeBlock = (letter: string) => {
        const next = blocksList.filter((b) => b !== letter);
        setBlocksList(next);
        setData('blocks', next);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        transform((formData) => ({
            ...formData,
            total_lots: formData.total_lots === '' ? null : Number(formData.total_lots),
            _method: 'put',
        }));
        post('/inmopro/projects/' + project.id, { forceFormData: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${project.name} - Inmopro`} />
            <div className="p-4">
                <h2 className="mb-6 text-2xl font-black text-slate-800">Editar Proyecto</h2>
                <form onSubmit={submit} className="max-w-md space-y-4">
                    <div>
                        <Label htmlFor="name">Nombre</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1" />
                        <InputError message={errors.name} />
                    </div>
                    <div>
                        <Label htmlFor="location">Ubicación</Label>
                        <Input id="location" value={data.location} onChange={(e) => setData('location', e.target.value)} className="mt-1" />
                        <InputError message={errors.location} />
                    </div>
                    <div>
                        <Label htmlFor="total_lots">Total de lotes (opcional)</Label>
                        <Input
                            id="total_lots"
                            type="number"
                            min={0}
                            value={data.total_lots}
                            onChange={(e) => setData('total_lots', e.target.value === '' ? '' : Number(e.target.value))}
                            className="mt-1"
                        />
                        <InputError message={errors.total_lots} />
                    </div>
                    <div>
                        <Label>Manzanas / Bloques</Label>
                        <div className="mt-1 flex gap-2">
                            <Input value={blockInput} onChange={(e) => setBlockInput(e.target.value)} placeholder="Ej. A" onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), addBlock())} />
                            <Button type="button" variant="outline" onClick={addBlock}>Añadir</Button>
                        </div>
                        {blocksList.length > 0 && (
                            <div className="mt-2 flex flex-wrap gap-2">
                                {blocksList.map((b) => (
                                    <span key={b} className="inline-flex items-center gap-1 rounded-full bg-slate-200 px-2 py-0.5 text-sm font-medium">
                                        {b}
                                        <button type="button" onClick={() => removeBlock(b)} className="text-slate-500 hover:text-slate-700">×</button>
                                    </span>
                                ))}
                            </div>
                        )}
                    </div>
                    <div>
                        <Label htmlFor="image_files">Añadir imágenes</Label>
                        <Input
                            id="image_files"
                            type="file"
                            multiple
                            accept="image/*"
                            onChange={(e) => setData('image_files', Array.from(e.target.files ?? []))}
                            className="mt-1"
                        />
                        <InputError message={errors.image_files || errors['image_files.0']} />
                    </div>
                    <div>
                        <Label htmlFor="document_files">Añadir documentos</Label>
                        <Input
                            id="document_files"
                            type="file"
                            multiple
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx"
                            onChange={(e) => setData('document_files', Array.from(e.target.files ?? []))}
                            className="mt-1"
                        />
                        <InputError message={errors.document_files || errors['document_files.0']} />
                    </div>
                    {project.assets && project.assets.length > 0 && (
                        <div className="space-y-3 rounded-xl border border-slate-200 p-4">
                            <h3 className="text-sm font-bold uppercase tracking-wide text-slate-500">Adjuntos actuales</h3>
                            <div className="space-y-2">
                                {project.assets.map((asset) => (
                                    <div key={asset.id} className="flex items-center justify-between rounded-lg border border-slate-200 px-3 py-2">
                                        <div>
                                            <p className="font-medium text-slate-800">{asset.title || asset.file_name}</p>
                                            <p className="text-xs text-slate-500">{asset.kind === 'image' ? 'Imagen' : 'Documento'}</p>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button type="button" variant="outline" onClick={() => window.open(asset.download_url, '_blank')}>
                                                Descargar
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="destructive"
                                                onClick={() => router.delete(`/inmopro/projects/${project.id}/assets/${asset.id}`)}
                                            >
                                                Eliminar
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                    <Button type="submit" disabled={processing}>Actualizar</Button>
                </form>
            </div>
        </AppLayout>
    );
}
