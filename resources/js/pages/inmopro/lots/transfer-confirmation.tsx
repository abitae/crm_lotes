import { Head, Link, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import AppLayout from '@/layouts/app-layout';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';

type Lot = {
    id: number;
    block: string;
    number: number;
    price?: string;
    project?: { id: number; name: string } | null;
    status?: { code: string; name: string; color?: string | null } | null;
    client?: { id: number; name: string } | null;
    advisor?: { id: number; name: string } | null;
};

type TransferConfirmationForm = {
    evidence_image: File | null;
    observations: string;
};

export default function LotTransferConfirmation({ lot }: { lot: Lot }) {
    const { data, setData, post, processing, errors } = useForm<TransferConfirmationForm>({
        evidence_image: null,
        observations: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Confirmación de transferencias', href: '/inmopro/lot-transfer-confirmations' },
        { title: `Lote ${lot.block}-${lot.number}`, href: `/inmopro/lots/${lot.id}` },
        { title: 'Confirmar transferencia', href: `/inmopro/lots/${lot.id}/transfer-confirmation` },
    ];

    const submit = (event: FormEvent) => {
        event.preventDefault();

        post(`/inmopro/lots/${lot.id}/transfer-confirmation`, {
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Confirmar transferencia ${lot.block}-${lot.number} - Inmopro`} />

            <div className="space-y-6 p-4">
                <div>
                    <h2 className="text-2xl font-black text-slate-800">
                        Confirmar transferencia
                    </h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Esta acción cambiará el lote a <strong>TRANSFERIDO</strong> y
                        generará las comisiones correspondientes.
                    </p>
                </div>

                <div className="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div>
                            <p className="text-sm text-slate-500">Lote</p>
                            <p className="font-semibold text-slate-800">
                                {lot.block}-{lot.number}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-slate-500">Estado actual</p>
                            <p className="font-semibold text-slate-800">
                                {lot.status?.name ?? '—'}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-slate-500">Proyecto</p>
                            <p className="font-semibold text-slate-800">
                                {lot.project?.name ?? '—'}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-slate-500">Cliente</p>
                            <p className="font-semibold text-slate-800">
                                {lot.client?.name ?? '—'}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-slate-500">Asesor</p>
                            <p className="font-semibold text-slate-800">
                                {lot.advisor?.name ?? '—'}
                            </p>
                        </div>
                        <div>
                            <p className="text-sm text-slate-500">Precio</p>
                            <p className="font-semibold text-slate-800">
                                {lot.price ? `S/ ${Number(lot.price).toLocaleString('es-PE')}` : '—'}
                            </p>
                        </div>
                    </div>
                </div>

                <form
                    onSubmit={submit}
                    className="space-y-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
                >
                    <div>
                        <Label htmlFor="evidence_image">Imagen de sustento</Label>
                        <Input
                            id="evidence_image"
                            type="file"
                            accept="image/*"
                            className="mt-1"
                            onChange={(event) =>
                                setData('evidence_image', event.target.files?.[0] ?? null)
                            }
                        />
                        <p className="mt-1 text-xs text-slate-500">
                            Suba una imagen que respalde la transferencia.
                        </p>
                        <InputError message={errors.evidence_image} />
                    </div>

                    <div>
                        <Label htmlFor="observations">Observaciones</Label>
                        <textarea
                            id="observations"
                            rows={4}
                            value={data.observations}
                            onChange={(event) => setData('observations', event.target.value)}
                            className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2"
                            placeholder="Opcional"
                        />
                        <InputError message={errors.observations} />
                    </div>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Confirmando...' : 'Confirmar transferencia'}
                        </Button>
                        <Button type="button" variant="outline" asChild>
                            <Link href="/inmopro/lot-transfer-confirmations">Cancelar</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
