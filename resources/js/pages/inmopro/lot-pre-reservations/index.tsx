import { Head, router, useForm } from '@inertiajs/react';
import { Eye } from 'lucide-react';
import type { FormEvent } from 'react';
import AppLayout from '@/layouts/app-layout';
import Pagination, { type PaginationLink } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';
import { formatDateTime } from '@/lib/date';

type ProjectOption = { id: number; name: string };
type AdvisorOption = { id: number; name: string };
type PreReservation = {
    id: number;
    status: string;
    amount: string;
    payment_reference?: string | null;
    notes?: string | null;
    rejection_reason?: string | null;
    voucher_path: string;
    created_at: string;
    reviewed_at?: string | null;
    lot?: { id: number; block: string; number: number; project?: { name: string } | null; status?: { name: string; code: string } | null } | null;
    client?: { name: string; city?: { name: string } | null } | null;
    advisor?: { name: string; team?: { name: string } | null } | null;
    reviewer?: { name: string } | null;
};

export default function LotPreReservationsIndex({
    preReservations,
    filters,
    projects,
    advisors,
}: {
    preReservations: { data: PreReservation[]; links: PaginationLink[] };
    filters: { status?: string; project_id?: number | string; advisor_id?: number | string };
    projects: ProjectOption[];
    advisors: AdvisorOption[];
}) {
    const form = useForm({
        status: filters.status ?? '',
        project_id: filters.project_id ? String(filters.project_id) : '',
        advisor_id: filters.advisor_id ? String(filters.advisor_id) : '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Pre-reservas', href: '/inmopro/lot-pre-reservations' },
    ];

    const submitFilters = (event: FormEvent) => {
        event.preventDefault();
        router.get('/inmopro/lot-pre-reservations', {
            status: form.data.status || undefined,
            project_id: form.data.project_id || undefined,
            advisor_id: form.data.advisor_id || undefined,
        }, { preserveState: true });
    };

    const approve = (id: number) => {
        router.post(`/inmopro/lot-pre-reservations/${id}/approve`);
    };

    const reject = (id: number) => {
        const reason = window.prompt('Motivo del rechazo');
        if (!reason) {
            return;
        }

        router.post(`/inmopro/lot-pre-reservations/${id}/reject`, {
            rejection_reason: reason,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pre-reservas - Inmopro" />
            <div className="space-y-6 p-4">
                <div>
                    <h2 className="text-2xl font-black text-slate-800">Pre-reservas de unidades</h2>
                    <p className="text-sm text-slate-500">Revise vouchers y apruebe la reserva final desde el backend.</p>
                </div>

                <form onSubmit={submitFilters} className="grid gap-4 rounded-2xl border border-slate-200 bg-white p-4 md:grid-cols-4">
                    <select value={form.data.status} onChange={(event) => form.setData('status', event.target.value)} className="rounded-lg border border-slate-200 px-3 py-2">
                        <option value="">Todos los estados</option>
                        <option value="PENDIENTE">Pendiente</option>
                        <option value="APROBADA">Aprobada</option>
                        <option value="RECHAZADA">Rechazada</option>
                    </select>
                    <select value={form.data.project_id} onChange={(event) => form.setData('project_id', event.target.value)} className="rounded-lg border border-slate-200 px-3 py-2">
                        <option value="">Todos los proyectos</option>
                        {projects.map((project) => (
                            <option key={project.id} value={project.id}>{project.name}</option>
                        ))}
                    </select>
                    <select value={form.data.advisor_id} onChange={(event) => form.setData('advisor_id', event.target.value)} className="rounded-lg border border-slate-200 px-3 py-2">
                        <option value="">Todos los vendedores</option>
                        {advisors.map((advisor) => (
                            <option key={advisor.id} value={advisor.id}>{advisor.name}</option>
                        ))}
                    </select>
                    <Button type="submit">Filtrar</Button>
                </form>

                <div className="overflow-hidden rounded-2xl border border-slate-200 bg-white">
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead className="bg-slate-50">
                                <tr>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Unidad</th>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Cliente</th>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Vendedor</th>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Estado</th>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Monto</th>
                                    <th className="px-4 py-3 text-left font-bold text-slate-600">Fecha</th>
                                    <th className="px-4 py-3 text-right font-bold text-slate-600">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {preReservations.data.map((preReservation) => (
                                    <tr key={preReservation.id}>
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-slate-800">{preReservation.lot?.project?.name ?? '-'}</div>
                                            <div className="text-xs text-slate-500">
                                                {preReservation.lot ? `${preReservation.lot.block}-${preReservation.lot.number}` : '-'}
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-slate-800">{preReservation.client?.name ?? '-'}</div>
                                            <div className="text-xs text-slate-500">{preReservation.client?.city?.name ?? 'Sin ciudad'}</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="font-medium text-slate-800">{preReservation.advisor?.name ?? '-'}</div>
                                            <div className="text-xs text-slate-500">{preReservation.advisor?.team?.name ?? '-'}</div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="text-slate-700">{preReservation.status}</div>
                                            {preReservation.rejection_reason ? (
                                                <div className="mt-1 text-xs text-red-600">{preReservation.rejection_reason}</div>
                                            ) : null}
                                        </td>
                                        <td className="px-4 py-3 font-medium text-slate-700">
                                            S/ {Number(preReservation.amount).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                        </td>
                                        <td className="px-4 py-3 text-slate-600">
                                            <div>{formatDateTime(preReservation.created_at)}</div>
                                            {preReservation.reviewed_at ? <div className="text-xs text-slate-400">Revisión: {formatDateTime(preReservation.reviewed_at)}</div> : null}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="flex justify-end gap-2">
                                                <a href={`/storage/${preReservation.voucher_path}`} target="_blank" rel="noreferrer" className="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-600 hover:bg-slate-50">
                                                    <Eye className="h-4 w-4" />
                                                </a>
                                                {preReservation.status === 'PENDIENTE' ? (
                                                    <>
                                                        <Button type="button" size="sm" onClick={() => approve(preReservation.id)}>Aprobar</Button>
                                                        <Button type="button" size="sm" variant="outline" onClick={() => reject(preReservation.id)}>Rechazar</Button>
                                                    </>
                                                ) : null}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    <div className="border-t border-slate-100 px-4 py-3">
                        <Pagination links={preReservations.links} />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
