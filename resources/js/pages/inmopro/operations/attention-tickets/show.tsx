import { Head, Link, router } from '@inertiajs/react';
import { Calendar, FileText, Pencil, PenLine, User } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

type Project = { id: number; name: string; location?: string };
type Client = { id: number; name: string; dni?: string; phone?: string };
type Advisor = { id: number; name: string };
type Lot = { id: number; block: string; number: number; area?: string; price?: string; project?: Project; client?: Client | null };
type DeliveryDeed = { id: number; printed_at: string | null; signed_at: string | null } | null;
type Ticket = {
    id: number;
    scheduled_at: string;
    status: string;
    notes: string | null;
    advisor: Advisor;
    lot: Lot;
    delivery_deed: DeliveryDeed;
};

export default function AttentionTicketsShow({ ticket }: { ticket: Ticket }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Operaciones', href: '/inmopro/attention-tickets' },
        { title: 'Tickets de atención', href: '/inmopro/attention-tickets' },
        { title: `Ticket #${ticket.id}`, href: `/inmopro/attention-tickets/${ticket.id}` },
    ];

    const statusLabels: Record<string, string> = {
        pendiente: 'Pendiente',
        agendado: 'Agendado',
        realizado: 'Realizado',
        cancelado: 'Cancelado',
    };

    const handlePrintDeed = () => {
        window.open(`/inmopro/attention-tickets/${ticket.id}/delivery-deed`, '_blank', 'noopener,noreferrer');
    };

    const handleMarkSigned = () => {
        router.post(`/inmopro/attention-tickets/${ticket.id}/delivery-deed/mark-signed`, {}, { preserveScroll: true });
    };

    const deed = ticket.delivery_deed;
    const isSigned = deed?.signed_at != null;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Ticket #${ticket.id} - Operaciones - Inmopro`} />
            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">Ticket #{ticket.id}</h1>
                        <p className="mt-1 text-sm text-slate-500">
                            {new Date(ticket.scheduled_at).toLocaleString('es-PE', { dateStyle: 'long', timeStyle: 'short' })}
                            {' · '}
                            <span className="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700">
                                {statusLabels[ticket.status] ?? ticket.status}
                            </span>
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/inmopro/attention-tickets/${ticket.id}/edit`}>
                                <Pencil className="h-4 w-4" />
                                Editar
                            </Link>
                        </Button>
                        <Button size="sm" onClick={handlePrintDeed}>
                            <FileText className="h-4 w-4" />
                            Imprimir acta de entrega
                        </Button>
                        {!isSigned && (
                            <Button size="sm" variant="secondary" onClick={handleMarkSigned}>
                                <PenLine className="h-4 w-4" />
                                Registrar firma del cliente
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Calendar className="h-5 w-5" />
                                Lote y proyecto
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1 text-sm">
                            <p><span className="font-medium text-slate-600">Lote:</span> {ticket.lot?.block}-{ticket.lot?.number}</p>
                            <p><span className="font-medium text-slate-600">Proyecto:</span> {ticket.lot?.project?.name ?? '—'}</p>
                            {ticket.lot?.project?.location && (
                                <p><span className="font-medium text-slate-600">Ubicación:</span> {ticket.lot.project.location}</p>
                            )}
                            {ticket.lot?.area != null && <p><span className="font-medium text-slate-600">Área:</span> {ticket.lot.area}</p>}
                            {ticket.lot?.price != null && <p><span className="font-medium text-slate-600">Precio:</span> {ticket.lot.price}</p>}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <User className="h-5 w-5" />
                                Cliente
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-1 text-sm">
                            <p><span className="font-medium text-slate-600">Nombre:</span> {ticket.lot?.client?.name ?? '—'}</p>
                            {ticket.lot?.client?.dni && <p><span className="font-medium text-slate-600">DNI:</span> {ticket.lot.client.dni}</p>}
                            {ticket.lot?.client?.phone && <p><span className="font-medium text-slate-600">Teléfono:</span> {ticket.lot.client.phone}</p>}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Vendedor y acta</CardTitle>
                        <CardDescription>Quien solicitó el ticket y estado del acta de entrega.</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        <p><span className="font-medium text-slate-600">Vendedor que solicitó:</span> {ticket.advisor?.name ?? '—'}</p>
                        {deed?.printed_at && (
                            <p><span className="font-medium text-slate-600">Acta impresa:</span> {new Date(deed.printed_at).toLocaleString('es-PE')}</p>
                        )}
                        {deed?.signed_at && (
                            <p><span className="font-medium text-slate-600">Firma registrada:</span> {new Date(deed.signed_at).toLocaleString('es-PE')}</p>
                        )}
                        {ticket.notes && (
                            <p className="pt-2"><span className="font-medium text-slate-600">Observaciones:</span> {ticket.notes}</p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
