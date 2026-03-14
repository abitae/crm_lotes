import { Head, useForm } from '@inertiajs/react';
import { FormEvent, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';

type ProjectCreateForm = {
    name: string;
    location: string;
    total_lots: string | number;
    blocks: string[];
};

export default function ProjectsCreate() {
    const [blockInput, setBlockInput] = useState('');
    const [blocks, setBlocks] = useState<string[]>([]);
    const { data, setData, post, processing, errors, transform } = useForm<ProjectCreateForm>({
            name: '',
            location: '',
            total_lots: '' as string | number,
            blocks: [] as string[],
        });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Proyectos', href: '/inmopro/projects' },
        { title: 'Nuevo', href: '/inmopro/projects/create' },
    ];

    const addBlock = () => {
        const v = blockInput.trim().toUpperCase();
        if (v && !blocks.includes(v)) {
            const next = [...blocks, v];
            setBlocks(next);
            setData('blocks', next);
            setBlockInput('');
        }
    };

    const removeBlock = (letter: string) => {
        const next = blocks.filter((b) => b !== letter);
        setBlocks(next);
        setData('blocks', next);
    };

    const submit = (e: FormEvent) => {
        e.preventDefault();
        transform((formData) => ({
            ...formData,
            total_lots: formData.total_lots === '' ? null : Number(formData.total_lots),
        }));
        post('/inmopro/projects');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo Proyecto - Inmopro" />
            <div className="p-4">
                <h2 className="mb-6 text-2xl font-black text-slate-800">Nuevo Proyecto</h2>
                <form onSubmit={submit} className="max-w-md space-y-4">
                    <div>
                        <Label htmlFor="name">Nombre</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1"
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div>
                        <Label htmlFor="location">Ubicación</Label>
                        <Input
                            id="location"
                            value={data.location}
                            onChange={(e) => setData('location', e.target.value)}
                            className="mt-1"
                        />
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
                            <Input
                                value={blockInput}
                                onChange={(e) => setBlockInput(e.target.value)}
                                placeholder="Ej. A"
                                onKeyDown={(e) => e.key === 'Enter' && (e.preventDefault(), addBlock())}
                            />
                            <Button type="button" variant="outline" onClick={addBlock}>
                                Añadir
                            </Button>
                        </div>
                        {blocks.length > 0 && (
                            <div className="mt-2 flex flex-wrap gap-2">
                                {blocks.map((b) => (
                                    <span
                                        key={b}
                                        className="inline-flex items-center gap-1 rounded-full bg-slate-200 px-2 py-0.5 text-sm font-medium"
                                    >
                                        {b}
                                        <button type="button" onClick={() => removeBlock(b)} className="text-slate-500 hover:text-slate-700">
                                            ×
                                        </button>
                                    </span>
                                ))}
                            </div>
                        )}
                    </div>
                    <Button type="submit" disabled={processing}>
                        Guardar
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
