import { Head, Link, router, useForm } from '@inertiajs/react';
import { Check, Eye, Search, Upload, X } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import InputError from '@/components/input-error';
import Pagination, { type PaginationLink } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime } from '@/lib/date';
import type { BreadcrumbItem } from '@/types';

type Project = { id: number; name: string };
type TransferConfirmation = {
    id: number;
    status: string;
    evidence_path: string;
    created_at: string;
    reviewed_at?: string | null;
    rejection_reason?: string | null;
    requester?: { name: string } | null;
    reviewer?: { name: string } | null;
} | null;
type LotRow = {
    id: number;
    block: string;
    number: number;
    status?: { name: string; code: string } | null;
    project?: { name: string } | null;
    client?: { name: string; dni?: string | null; phone?: string | null } | null;
    advisor?: { name: string } | null;
    latest_transfer_confirmation?: TransferConfirmation;
};

export default function LotTransferConfirmationsIndex({
    lots,
    filters,
    projects,
}: {
    lots: { data: LotRow[]; links: PaginationLink[] };
    filters: { project_id?: string; search?: string };
    projects: Project[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Transferencias', href: '/inmopro/lot-transfer-confirmations' },
    ];
    const [selectedTransfer, setSelectedTransfer] = useState<Exclude<TransferConfirmation, null> | null>(null);
    const [rejectOpen, setRejectOpen] = useState(false);
    const rejectForm = useForm({
        rejection_reason: '',
    });

    const submitFilters = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        const formData = new FormData(event.currentTarget);

        router.get('/inmopro/lot-transfer-confirmations', {
            project_id: (formData.get('project_id') as string) || undefined,
            search: (formData.get('search') as string) || undefined,
        }, { preserveState: true });
    };

    const approve = (transferId: number) => {
        router.post(`/inmopro/lot-transfer-confirmations/${transferId}/approve`);
    };

    const openRejectDialog = (transfer: Exclude<TransferConfirmation, null>) => {
        rejectForm.reset();
        setSelectedTransfer(transfer);
        setRejectOpen(true);
    };

    const submitReject = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!selectedTransfer) {
            return;
        }

        rejectForm.post(`/inmopro/lot-transfer-confirmations/${selectedTransfer.id}/reject`, {
            onSuccess: () => {
                setRejectOpen(false);
                setSelectedTransfer(null);
                rejectForm.reset();
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transferencias - Inmopro" />
            <div className="space-y-6 p-4 md:p-6">
                <div>
                    <h2 className="text-2xl font-black text-slate-800">Confirmacion de transferencias</h2>
                    <p className="mt-1 text-sm text-slate-500">
                        Revise lotes reservados, registre la evidencia de transferencia y apruebe o rechace las revisiones pendientes.
                    </p>
                </div>

                <form onSubmit={submitFilters} className="grid gap-3 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-[240px_1fr_160px]">
                    <select
                        name="project_id"
                        defaultValue={filters.project_id}
                        className="rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm font-semibold text-slate-700 outline-none"
                    >
                        <option value="">Todos los proyectos</option>
                        {projects.map((project) => (
                            <option key={project.id} value={project.id}>
                                {project.name}
                            </option>
                        ))}
                    </select>
                    <div className="relative">
                        <Search className="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                        <Input
                            name="search"
                            defaultValue={filters.search}
                            placeholder="Buscar lote, cliente, DNI o telefono"
                            className="bg-slate-50 pl-10"
                        />
                    </div>
                    <Button type="submit">Filtrar</Button>
                </form>

                <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full min-w-[1100px] text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Lote</th>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Proyecto</th>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Cliente</th>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Asesor</th>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Estado lote</th>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Revision</th>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Registro</th>
                                    <th className="px-4 py-3 text-right font-bold text-slate-600">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {lots.data.length === 0 ? (
                                    <tr>
                                        <td colSpan={8} className="px-4 py-8 text-center text-sm text-slate-500">
                                            No se encontraron lotes para los filtros seleccionados.
                                        </td>
                                    </tr>
                                ) : (
                                    lots.data.map((lot) => {
                                        const transfer = lot.latest_transfer_confirmation;
                                        const canRegister = lot.status?.code === 'RESERVADO' && transfer?.status !== 'PENDIENTE';
                                        const isPending = transfer?.status === 'PENDIENTE';

                                        return (
                                            <tr key={lot.id} className="align-top">
                                                <td className="px-4 py-3">
                                                    <div className="font-semibold text-slate-800">{lot.block}-{lot.number}</div>
                                                    <div className="text-xs text-slate-500">ID #{lot.id}</div>
                                                </td>
                                                <td className="px-4 py-3 text-slate-700">
                                                    {lot.project?.name ?? '—'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="font-medium text-slate-800">{lot.client?.name ?? 'Sin cliente'}</div>
                                                    <div className="text-xs text-slate-500">
                                                        {[lot.client?.dni, lot.client?.phone].filter(Boolean).join(' · ') || 'Sin DNI / telefono'}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3 text-slate-700">
                                                    {lot.advisor?.name ?? '—'}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <span className="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-700">
                                                        {lot.status?.name ?? '—'}
                                                    </span>
                                                </td>
                                                <td className="px-4 py-3">
                                                    {transfer ? (
                                                        <>
                                                            <div className="font-medium text-slate-800">{transfer.status}</div>
                                                            <div className="text-xs text-slate-500">
                                                                Solicitado: {transfer.requester?.name ?? '—'}
                                                            </div>
                                                            {transfer.reviewer ? (
                                                                <div className="text-xs text-slate-500">
                                                                    Reviso: {transfer.reviewer.name}
                                                                </div>
                                                            ) : null}
                                                            {transfer.rejection_reason ? (
                                                                <div className="mt-1 text-xs text-red-600">{transfer.rejection_reason}</div>
                                                            ) : null}
                                                        </>
                                                    ) : (
                                                        <span className="text-sm text-slate-500">Sin transferencia registrada</span>
                                                    )}
                                                </td>
                                                <td className="px-4 py-3 text-slate-600">
                                                    {transfer ? (
                                                        <>
                                                            <div>{formatDateTime(transfer.created_at)}</div>
                                                            {transfer.reviewed_at ? (
                                                                <div className="text-xs text-slate-400">
                                                                    Revision: {formatDateTime(transfer.reviewed_at)}
                                                                </div>
                                                            ) : null}
                                                        </>
                                                    ) : (
                                                        '—'
                                                    )}
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex flex-wrap justify-end gap-2">
                                                        {transfer ? (
                                                            <a
                                                                href={`/storage/${transfer.evidence_path}`}
                                                                target="_blank"
                                                                rel="noreferrer"
                                                                className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50"
                                                                title="Ver evidencia"
                                                            >
                                                                <Eye className="h-4 w-4" />
                                                            </a>
                                                        ) : null}
                                                        {canRegister ? (
                                                            <Button type="button" size="sm" asChild>
                                                                <Link href={`/inmopro/lots/${lot.id}/transfer-confirmation`}>
                                                                    <Upload className="h-4 w-4" />
                                                                    Registrar
                                                                </Link>
                                                            </Button>
                                                        ) : null}
                                                        {isPending && transfer ? (
                                                            <>
                                                                <Button type="button" size="sm" onClick={() => approve(transfer.id)}>
                                                                    <Check className="h-4 w-4" />
                                                                    Aprobar
                                                                </Button>
                                                                <Button type="button" size="sm" variant="outline" onClick={() => openRejectDialog(transfer)}>
                                                                    <X className="h-4 w-4" />
                                                                    Rechazar
                                                                </Button>
                                                            </>
                                                        ) : null}
                                                    </div>
                                                </td>
                                            </tr>
                                        );
                                    })
                                )}
                            </tbody>
                        </table>
                    </div>
                    <div className="border-t border-slate-100 px-4 py-3">
                        <Pagination links={lots.links} />
                    </div>
                </div>
            </div>

            <Dialog open={rejectOpen} onOpenChange={(open) => {
                setRejectOpen(open);

                if (!open) {
                    setSelectedTransfer(null);
                    rejectForm.reset();
                }
            }}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Rechazar transferencia</DialogTitle>
                        <DialogDescription>
                            Indique el motivo del rechazo para devolver el lote al estado reservado.
                        </DialogDescription>
                    </DialogHeader>
                    <form onSubmit={submitReject} className="space-y-4">
                        <textarea
                            value={rejectForm.data.rejection_reason}
                            onChange={(event) => rejectForm.setData('rejection_reason', event.target.value)}
                            rows={4}
                            className="w-full rounded-lg border border-slate-200 px-3 py-2 text-sm outline-none"
                            placeholder="Motivo del rechazo"
                        />
                        <InputError message={rejectForm.errors.rejection_reason} />
                        <DialogFooter>
                            <Button type="button" variant="outline" onClick={() => setRejectOpen(false)}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={rejectForm.processing}>
                                Confirmar rechazo
                            </Button>
                        </DialogFooter>
                    </form>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
