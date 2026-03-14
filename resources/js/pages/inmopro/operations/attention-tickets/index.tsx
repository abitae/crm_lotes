import { Head, Link, router } from '@inertiajs/react';
import { Calendar, Eye, Plus, Ticket } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import Pagination, { type PaginationLink } from '@/components/pagination';
import { formatDateTime } from '@/lib/date';
import type { BreadcrumbItem } from '@/types';

type Project = { id: number; name: string };
type Client = { id: number; name: string; dni?: string };
type Advisor = { id: number; name: string };
type Lot = { id: number; block: string; number: number } | null;
type DeliveryDeed = { id: number; printed_at: string | null; signed_at: string | null } | null;
type TicketItem = {
    id: number;
    created_at: string;
    scheduled_at: string | null;
    status: string;
    notes: string | null;
    advisor: Advisor | null;
    client: Client | null;
    project: Project | null;
    lot: Lot;
    delivery_deed: DeliveryDeed;
};

export default function AttentionTicketsIndex({
    tickets,
    filters,
}: {
    tickets: { data: TicketItem[]; links: PaginationLink[]; current_page: number; last_page: number };
    filters: { status?: string };
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Operaciones', href: '/inmopro/attention-tickets' },
        { title: 'Tickets de atención', href: '/inmopro/attention-tickets' },
    ];

    const statusLabels: Record<string, string> = {
        pendiente: 'Pendiente',
        agendado: 'Agendado',
        realizado: 'Realizado',
        cancelado: 'Cancelado',
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tickets de atención - Operaciones - Inmopro" />
            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">Tickets de atención</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            Solicitudes creadas por vendedor y agendadas desde administración.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href="/inmopro/attention-tickets/calendar">
                                <Calendar className="h-4 w-4" />
                                Ver calendario
                            </Link>
                        </Button>
                        <Button size="sm" asChild>
                            <Link href="/inmopro/attention-tickets/create">
                                <Plus className="h-4 w-4" />
                                Nuevo ticket
                            </Link>
                        </Button>
                    </div>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtrar por estado</CardTitle>
                        <CardDescription>Opcional.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex flex-wrap gap-2">
                            <Button variant={!filters.status ? 'secondary' : 'outline'} size="sm" onClick={() => router.get('/inmopro/attention-tickets')}>
                                Todos
                            </Button>
                            {['pendiente', 'agendado', 'realizado', 'cancelado'].map((status) => (
                                <Button
                                    key={status}
                                    variant={filters.status === status ? 'secondary' : 'outline'}
                                    size="sm"
                                    onClick={() => router.get('/inmopro/attention-tickets', { status }, { preserveState: true })}
                                >
                                    {statusLabels[status] ?? status}
                                </Button>
                            ))}
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Listado</CardTitle>
                        <CardDescription>{tickets.data.length} ticket(s).</CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        {tickets.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-16 text-center">
                                <div className="rounded-full bg-slate-100 p-4">
                                    <Ticket className="h-10 w-10 text-slate-400" />
                                </div>
                                <p className="mt-4 font-medium text-slate-700">Sin tickets</p>
                                <p className="mt-1 text-sm text-slate-500">Cree una solicitud para iniciar el flujo de atención.</p>
                                <Button className="mt-4" variant="outline" asChild>
                                    <Link href="/inmopro/attention-tickets/create">Nuevo ticket</Link>
                                </Button>
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-slate-100 bg-slate-50/80">
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Solicitud</th>
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Agendado para</th>
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Estado</th>
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Vendedor</th>
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Cliente</th>
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Proyecto</th>
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Lote legado</th>
                                                <th className="px-4 py-3 text-right font-medium text-slate-600">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-50">
                                            {tickets.data.map((ticket) => (
                                                <tr key={ticket.id} className="hover:bg-slate-50/50">
                                                    <td className="px-4 py-3 text-slate-700">{formatDateTime(ticket.created_at)}</td>
                                                    <td className="px-4 py-3 text-slate-700">{formatDateTime(ticket.scheduled_at)}</td>
                                                    <td className="px-4 py-3">
                                                        <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                                            {statusLabels[ticket.status] ?? ticket.status}
                                                        </span>
                                                    </td>
                                                    <td className="px-4 py-3 text-slate-700">{ticket.advisor?.name ?? '-'}</td>
                                                    <td className="px-4 py-3 text-slate-700">{ticket.client?.name ?? '-'}</td>
                                                    <td className="px-4 py-3 text-slate-700">{ticket.project?.name ?? '-'}</td>
                                                    <td className="px-4 py-3 text-slate-700">
                                                        {ticket.lot ? `${ticket.lot.block}-${ticket.lot.number}` : '-'}
                                                    </td>
                                                    <td className="px-4 py-3 text-right">
                                                        <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                                                            <Link href={`/inmopro/attention-tickets/${ticket.id}`}>
                                                                <Eye className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                {tickets.data.length > 0 && (
                                    <div className="border-t border-slate-100 px-4 py-3">
                                        <Pagination links={tickets.links} />
                                    </div>
                                )}
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
