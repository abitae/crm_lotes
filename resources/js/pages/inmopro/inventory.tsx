import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Search, MapPin, UserPlus, Info, Eye } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type LotStatus = { id: number; name: string; code: string; color: string };
type Project = { id: number; name: string; blocks: string[] };
type Lot = {
    id: number;
    block: string;
    number: number;
    area: string;
    price: string;
    lot_status_id: number;
    client_id?: number;
    advisor_id?: number;
    client_name?: string;
    client_dni?: string;
    advance?: string;
    remaining_balance?: string;
    payment_limit_date?: string;
    operation_number?: string;
    contract_date?: string;
    contract_number?: string;
    status: LotStatus;
    client?: { id: number; name: string };
    advisor?: { id: number; name: string };
};
type Client = { id: number; name: string; dni: string; phone: string; email?: string };
type Advisor = { id: number; name: string; email: string; level?: { name: string } };

export default function Inventory({
    projects,
    project,
    lots,
    lotStatuses,
    clients,
    advisors,
}: {
    projects: Project[];
    project: Project | null;
    lots: Lot[];
    lotStatuses: LotStatus[];
    clients: Client[];
    advisors: Advisor[];
}) {
    const [searchTerm, setSearchTerm] = useState('');
    const [selectedLot, setSelectedLot] = useState<Lot | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Inventario', href: '/inmopro/lots' },
    ];

    const getStatusColor = (statusId: number) => {
        const status = lotStatuses.find((s) => s.id === statusId);
        if (status?.code === 'LIBRE') return 'bg-emerald-500 hover:bg-emerald-600';
        if (status?.code === 'RESERVADO') return 'bg-amber-500 hover:bg-amber-600';
        if (status?.code === 'TRANSFERIDO') return 'bg-slate-400 hover:bg-slate-500';
        return 'bg-slate-200';
    };

    const filteredLots = lots.filter(
        (l) =>
            l.id.toString().includes(searchTerm) ||
            l.number.toString().includes(searchTerm) ||
            l.block.toLowerCase().includes(searchTerm.toLowerCase())
    );

    const blockGroups = project
        ? project.blocks.map((block) => ({
              block,
              lots: filteredLots.filter((l) => l.block === block).sort((a, b) => a.number - b.number),
          }))
        : [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Inventario - Inmopro" />
            <div className="flex h-full flex-col gap-8 p-4 lg:flex-row">
                <div className="flex min-w-0 flex-1 flex-col rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    {projects.length > 0 && (
                        <div className="mb-4 flex flex-col gap-2 sm:flex-row sm:items-end">
                            <div className="flex-1">
                                <label className="mb-2 block text-xs font-bold uppercase text-slate-500">
                                    Proyecto
                                </label>
                                <select
                                    value={project?.id ?? ''}
                                    onChange={(e) => {
                                        const id = e.target.value;
                                        router.get('/inmopro/lots', id ? { project_id: id } : {});
                                    }}
                                    className="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-bold text-slate-800 outline-none focus:ring-2 focus:ring-emerald-500"
                                >
                                    {projects.map((p) => (
                                        <option key={p.id} value={p.id}>
                                            {p.name}
                                        </option>
                                    ))}
                                </select>
                            </div>
                        </div>
                    )}
                    {project && (
                        <>
                            <div className="mb-8 flex flex-col gap-4 sm:flex-row">
                                <div className="relative flex-1">
                                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                    <input
                                        type="text"
                                        placeholder="Buscar por número o manzana..."
                                        className="w-full rounded-lg border border-slate-200 bg-slate-50 py-2 pl-10 pr-4 outline-none transition-all focus:border-emerald-500 focus:ring-emerald-500"
                                        value={searchTerm}
                                        onChange={(e) => setSearchTerm(e.target.value)}
                                    />
                                </div>
                                <div className="flex items-center gap-4 text-xs font-bold uppercase tracking-tight text-slate-400">
                                    {lotStatuses.slice(0, 3).map((s) => (
                                        <div key={s.id} className="flex items-center gap-1.5">
                                            <div
                                                className="h-3 w-3 rounded"
                                                style={{ backgroundColor: s.color }}
                                            />
                                            <span>{s.name}</span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div className="flex-1 space-y-10 overflow-y-auto pr-2">
                                {blockGroups.map(
                                    ({ block, lots: blockLots }) =>
                                        blockLots.length > 0 && (
                                            <section key={block} className="space-y-4">
                                                <div className="flex items-center gap-3 border-b border-slate-100 pb-2">
                                                    <div className="rounded-full bg-slate-900 px-3 py-1 text-xs font-black text-white">
                                                        MANZANA {block}
                                                    </div>
                                                    <span className="text-xs font-medium uppercase tracking-widest text-slate-400">
                                                        {blockLots.length} Lotes
                                                    </span>
                                                </div>
                                                <div className="grid grid-cols-6 gap-1.5 sm:grid-cols-10 md:grid-cols-14 lg:grid-cols-18">
                                                    {blockLots.map((lot) => (
                                                        <button
                                                            key={lot.id}
                                                            type="button"
                                                            onClick={() => setSelectedLot(lot)}
                                                            className={`aspect-square flex flex-col items-center justify-center rounded-md p-0.5 text-white shadow-sm transition-all hover:scale-105 active:scale-95 ${getStatusColor(lot.lot_status_id)} ${selectedLot?.id === lot.id ? 'ring-2 ring-emerald-400 ring-offset-1 z-10' : ''}`}
                                                        >
                                                            <span className="text-[10px] font-black leading-none">
                                                                {lot.number}
                                                            </span>
                                                            <span className="text-[7px] font-bold uppercase opacity-80">
                                                                {lot.area}m²
                                                            </span>
                                                        </button>
                                                    ))}
                                                </div>
                                            </section>
                                        )
                                )}
                            </div>
                        </>
                    )}
                    {!project && (
                        <div className="flex flex-1 items-center justify-center text-slate-500">
                            <p>Seleccione un proyecto desde la barra superior.</p>
                        </div>
                    )}
                </div>

                <div className="w-full shrink-0 lg:w-96">
                    {selectedLot ? (
                        <div className="sticky top-8 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                            <div
                                className="h-2"
                                style={{
                                    backgroundColor:
                                        lotStatuses.find((s) => s.id === selectedLot.lot_status_id)?.color ??
                                        '#94a3b8',
                                }}
                            />
                            <div className="p-6">
                                <div className="mb-6">
                                    <h3 className="text-2xl font-black leading-tight text-slate-800">
                                        Lote {selectedLot.number}
                                    </h3>
                                    <p className="text-sm font-semibold uppercase tracking-wide text-slate-400">
                                        Manzana {selectedLot.block} • {project?.name}
                                    </p>
                                </div>
                                <div className="mb-8 grid grid-cols-2 gap-4">
                                    <div className="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                        <p className="mb-1 text-[10px] font-bold uppercase text-slate-400">
                                            Precio
                                        </p>
                                        <p className="text-lg font-black text-slate-800">
                                            S/ {Number(selectedLot.price).toLocaleString()}
                                        </p>
                                    </div>
                                    <div className="rounded-xl border border-slate-100 bg-slate-50 p-3">
                                        <p className="mb-1 text-[10px] font-bold uppercase text-slate-400">
                                            Área
                                        </p>
                                        <p className="text-lg font-black text-slate-800">
                                            {selectedLot.area} m²
                                        </p>
                                    </div>
                                </div>
                                {(selectedLot.client_id || selectedLot.client_name) ? (
                                    <div className="mb-8 space-y-4">
                                        <h4 className="flex items-center gap-2 border-b border-slate-100 pb-2 text-xs font-black uppercase tracking-widest text-slate-900">
                                            <UserPlus className="h-4 w-4 text-emerald-500" />
                                            Asignación (solo si se reserva)
                                        </h4>
                                        <div className="space-y-3 rounded-xl bg-slate-50 p-4">
                                            <div>
                                                <p className="text-[10px] font-bold uppercase text-slate-400">
                                                    Nombre cliente
                                                </p>
                                                <p className="text-sm font-bold text-slate-800">
                                                    {selectedLot.client_name ?? selectedLot.client?.name ?? '-'}
                                                </p>
                                            </div>
                                            {selectedLot.client_dni && (
                                                <div>
                                                    <p className="text-[10px] font-bold uppercase text-slate-400">
                                                        DNI cliente
                                                    </p>
                                                    <p className="text-sm font-bold text-slate-800">{selectedLot.client_dni}</p>
                                                </div>
                                            )}
                                            <div>
                                                <p className="text-[10px] font-bold uppercase text-slate-400">
                                                    Asesor
                                                </p>
                                                <p className="text-sm font-bold text-slate-800">
                                                    {selectedLot.advisor?.name ?? '-'}
                                                </p>
                                            </div>
                                            {(selectedLot.advance != null || selectedLot.remaining_balance != null) && (
                                                <div className="grid grid-cols-2 gap-2 border-t border-slate-200 pt-3">
                                                    <div>
                                                        <p className="text-[10px] font-bold uppercase text-slate-400">Adelanto</p>
                                                        <p className="text-sm font-bold text-slate-800">
                                                            S/ {Number(selectedLot.advance || 0).toLocaleString()}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p className="text-[10px] font-bold uppercase text-slate-400">Monto restante</p>
                                                        <p className="text-sm font-bold text-slate-800">
                                                            S/ {Number(selectedLot.remaining_balance || 0).toLocaleString()}
                                                        </p>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ) : (
                                    <div className="mb-8 rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                                        <p className="flex items-start gap-2 text-xs font-medium text-emerald-700">
                                            <Info className="mt-0.5 h-4 w-4 shrink-0" />
                                            Lote disponible. Puede reservar o transferir.
                                        </p>
                                    </div>
                                )}
                                <div>
                                    <Link
                                        href={'/inmopro/lots/' + selectedLot.id}
                                        className="flex w-full items-center justify-center gap-2 rounded-xl border border-slate-200 py-3 px-4 font-bold text-slate-600 hover:bg-slate-50"
                                    >
                                        <Eye className="h-4 w-4" />
                                        Ver detalle
                                    </Link>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="flex h-[500px] flex-col items-center justify-center rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 p-10 text-center">
                            <div className="mb-6 flex h-16 w-16 items-center justify-center rounded-2xl bg-white shadow-sm">
                                <MapPin className="h-8 w-8 text-slate-300" />
                            </div>
                            <h4 className="mb-2 font-black uppercase tracking-tight text-slate-800">
                                Mapa Interactivo
                            </h4>
                            <p className="mx-auto max-w-[200px] text-sm leading-relaxed text-slate-400">
                                Seleccione un lote para ver su ficha.
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
