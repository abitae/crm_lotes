import { Head, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';

type Project = { id: number; name: string; location?: string; total_lots?: number; blocks?: string[] };

export default function ProjectsEdit({ project }: { project: Project }) {
    const blocks = project.blocks ?? [];
    const [blockInput, setBlockInput] = useState('');
    const [blocksList, setBlocksList] = useState<string[]>(blocks);
    const { data, setData, put, processing, errors } = useForm(
        {
            name: project.name,
            location: project.location ?? '',
            total_lots: project.total_lots ?? ('' as number | ''),
            blocks: blocksList,
        },
        {
            transform: (formData) => ({
                ...formData,
                total_lots: formData.total_lots === '' ? null : Number(formData.total_lots),
            }),
        }
    );

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
        put('/inmopro/projects/' + project.id);
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
                            onChange={(e) => setData('total_lots', e.target.value === '' ? '' : e.target.value)}
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
                    <Button type="submit" disabled={processing}>Actualizar</Button>
                </form>
            </div>
        </AppLayout>
    );
}
