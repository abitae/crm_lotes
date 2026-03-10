import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';

type Project = { id: number; name: string };
type Client = { id: number; name: string };
type Lot = {
    id: number;
    block: string;
    number: number;
    project?: Project;
    client?: Client | null;
    client_name?: string;
};
type Advisor = { id: number; name: string };

export default function AttentionTicketsCreate({
    lots,
    advisors,
}: {
    lots: Lot[];
    advisors: Advisor[];
}) {
    const defaultDateTime = () => {
        const d = new Date();
        d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
        return d.toISOString().slice(0, 16);
    };

    const { data, setData, post, processing, errors } = useForm({
        advisor_id: '',
        lot_id: '',
        scheduled_at: defaultDateTime(),
        notes: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Operaciones', href: '/inmopro/attention-tickets' },
        { title: 'Tickets de atención', href: '/inmopro/attention-tickets' },
        { title: 'Nuevo', href: '/inmopro/attention-tickets/create' },
    ];

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/inmopro/attention-tickets');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo ticket de atención - Operaciones - Inmopro" />
            <div className="p-4 md:p-6">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold tracking-tight text-slate-900">Nuevo ticket de atención</h1>
                    <p className="mt-1 text-sm text-slate-500">Solicite un acta de entrega y agenda fecha y hora.</p>
                </div>
                <Card className="max-w-lg">
                    <CardHeader>
                        <CardTitle>Datos del ticket</CardTitle>
                        <CardDescription>Seleccione el lote y el vendedor que solicita. Solo se listan lotes con cliente asignado.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-4">
                            <div>
                                <Label htmlFor="advisor_id">Vendedor que solicita</Label>
                                <select
                                    id="advisor_id"
                                    value={data.advisor_id}
                                    onChange={(e) => setData('advisor_id', e.target.value)}
                                    className="mt-1 flex h-9 w-full rounded-md border border-slate-200 bg-white px-3 py-1 text-sm"
                                    required
                                >
                                    <option value="">— Seleccione —</option>
                                    {advisors.map((a) => (
                                        <option key={a.id} value={a.id}>{a.name}</option>
                                    ))}
                                </select>
                                <InputError message={errors.advisor_id} />
                            </div>
                            <div>
                                <Label htmlFor="lot_id">Lote</Label>
                                <select
                                    id="lot_id"
                                    value={data.lot_id}
                                    onChange={(e) => setData('lot_id', e.target.value)}
                                    className="mt-1 flex h-9 w-full rounded-md border border-slate-200 bg-white px-3 py-1 text-sm"
                                    required
                                >
                                    <option value="">— Seleccione —</option>
                                    {lots.map((lot) => (
                                        <option key={lot.id} value={lot.id}>
                                            {lot.block}-{lot.number} · {lot.project?.name ?? 'Proyecto'} · {lot.client?.name ?? lot.client_name ?? 'Cliente'}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={errors.lot_id} />
                            </div>
                            <div>
                                <Label htmlFor="scheduled_at">Fecha y hora</Label>
                                <Input
                                    id="scheduled_at"
                                    type="datetime-local"
                                    value={data.scheduled_at}
                                    onChange={(e) => setData('scheduled_at', e.target.value)}
                                    className="mt-1"
                                    required
                                />
                                <InputError message={errors.scheduled_at} />
                            </div>
                            <div>
                                <Label htmlFor="notes">Observaciones</Label>
                                <textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    className="mt-1 flex min-h-[80px] w-full rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"
                                    rows={3}
                                />
                                <InputError message={errors.notes} />
                            </div>
                            <div className="flex gap-2 pt-2">
                                <Button type="submit" disabled={processing}>
                                    Crear ticket
                                </Button>
                                <Button type="button" variant="outline" asChild>
                                    <a href="/inmopro/attention-tickets">Cancelar</a>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
