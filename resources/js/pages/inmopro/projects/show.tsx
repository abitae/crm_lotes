import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState, useRef, useCallback } from 'react';
import { MapPin, Pencil, LayoutGrid, Plus, Eye, Save } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription } from '@/components/ui/alert';
import type { BreadcrumbItem } from '@/types';

type LotStatus = { id: number; name: string; code: string; color?: string };
type Client = { id: number; name: string; dni?: string; phone?: string };
type Advisor = { id: number; name: string };

type Lot = {
    id: number;
    block: string;
    number: number;
    area?: string | number;
    price?: string | number;
    status?: LotStatus | null;
    client?: Client | null;
    advisor?: Advisor | null;
    client_name?: string | null;
    client_dni?: string | null;
    client_phone?: string | null;
    advance?: string | number | null;
    remaining_balance?: string | number | null;
    payment_limit_date?: string | null;
    operation_number?: string | null;
    contract_date?: string | null;
    contract_number?: string | null;
    notarial_transfer_date?: string | null;
    observations?: string | null;
};

type Project = {
    id: number;
    name: string;
    location?: string;
    total_lots?: number;
    blocks?: string[];
    lots?: Lot[];
};

type LotPayload = {
    lot_status_id: number;
    client_id: number | null;
    advisor_id: number | null;
    client_name?: string | null;
    client_dni?: string | null;
    client_phone?: string | null;
    advance?: number | null;
    remaining_balance?: number | null;
    payment_limit_date?: string | null;
    operation_number?: string | null;
    contract_date?: string | null;
    contract_number?: string | null;
    notarial_transfer_date?: string | null;
    observations?: string | null;
    block?: string;
    number?: number;
    area?: number | null;
    price?: number | null;
};

type PageProps = {
    project: Project;
    lotStatuses: LotStatus[];
    errors?: Record<string, string>;
};

function toDateStr(v: string | undefined | null): string {
    if (v == null || v === '') return '';
    const s = String(v);
    if (s.includes('T')) return s.slice(0, 10);
    return s.slice(0, 10);
}

function toNum(v: string | number | undefined | null): number | null {
    if (v == null || v === '') return null;
    const n = Number(v);
    return Number.isNaN(n) ? null : n;
}

const SEARCH_DEBOUNCE_MS = 250;
const SEARCH_MIN_CHARS = 2;
const SEARCH_CACHE_MAX = 50;

async function searchClients(q: string): Promise<Client[]> {
    const term = q.trim();
    if (!term) return [];
    const params = new URLSearchParams({ q: term });
    const res = await fetch(`/inmopro/clients/search?${params.toString()}`, { headers: { Accept: 'application/json' } });
    if (!res.ok) return [];
    return res.json();
}

async function searchAdvisors(q: string): Promise<Advisor[]> {
    const term = q.trim();
    if (!term) return [];
    const params = new URLSearchParams({ q: term });
    const res = await fetch(`/inmopro/advisors/search?${params.toString()}`, { headers: { Accept: 'application/json' } });
    if (!res.ok) return [];
    return res.json();
}

function useSearchableSelect<T extends { id: number }>(
    fetchFn: (q: string) => Promise<T[]>,
    options: { debounceMs: number; minChars: number; cacheMax?: number }
) {
    const { debounceMs, minChars, cacheMax = 0 } = options;
    const [openKey, setOpenKey] = useState<string | null>(null);
    const [results, setResults] = useState<T[]>([]);
    const [loading, setLoading] = useState(false);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const cacheRef = useRef<Map<string, T[]>>(new Map());

    const triggerSearch = useCallback(
        (q: string, key: string) => {
            if (debounceRef.current) clearTimeout(debounceRef.current);
            const term = q.trim();
            setOpenKey(key);
            if (term.length < minChars) {
                setResults([]);
                return;
            }
            if (cacheMax > 0) {
                const cacheKey = term.toLowerCase();
                const hit = cacheRef.current.get(cacheKey);
                if (hit !== undefined) {
                    setResults(hit);
                    return;
                }
            }
            debounceRef.current = setTimeout(() => {
                setLoading(true);
                fetchFn(term)
                    .then((data) => {
                        setResults(data);
                        if (cacheMax > 0 && data.length > 0) {
                            const cache = cacheRef.current;
                            if (cache.size >= cacheMax) {
                                const first = cache.keys().next().value;
                                if (first !== undefined) cache.delete(first);
                            }
                            cache.set(term.toLowerCase(), data);
                        }
                    })
                    .finally(() => setLoading(false));
            }, debounceMs);
        },
        [fetchFn, debounceMs, minChars, cacheMax]
    );

    return { openKey, setOpenKey, results, loading, triggerSearch };
}

export default function ProjectsShow({ project, lotStatuses }: PageProps) {
    const { errors } = usePage<PageProps>().props;
    const [savingLotId, setSavingLotId] = useState<number | null>(null);
    const [edited, setEdited] = useState<Record<number, Partial<Record<string, string | number | null>>>>({});
    const clientJustSelectedRef = useRef<{ lotId: number } | null>(null);

    const clientSearch = useSearchableSelect<Client>(searchClients, {
        debounceMs: SEARCH_DEBOUNCE_MS,
        minChars: SEARCH_MIN_CHARS,
        cacheMax: SEARCH_CACHE_MAX,
    });
    const advisorSearch = useSearchableSelect<Advisor>(searchAdvisors, {
        debounceMs: SEARCH_DEBOUNCE_MS,
        minChars: SEARCH_MIN_CHARS,
        cacheMax: SEARCH_CACHE_MAX,
    });
    const [advisorSearchTerm, setAdvisorSearchTerm] = useState<Record<number, string>>({});

    const buildPayload = (lot: Lot, overrides: Partial<LotPayload>): LotPayload => ({
        lot_status_id: lot.status?.id ?? lotStatuses[0]?.id ?? 0,
        client_id: overrides.client_id !== undefined ? overrides.client_id : (lot.client?.id ?? null),
        advisor_id: overrides.advisor_id !== undefined ? overrides.advisor_id : (lot.advisor?.id ?? null),
        client_name: lot.client_name ?? lot.client?.name ?? null,
        client_dni: lot.client_dni ?? lot.client?.dni ?? null,
        client_phone: lot.client_phone ?? lot.client?.phone ?? null,
        advance: toNum(lot.advance),
        remaining_balance: toNum(lot.remaining_balance),
        payment_limit_date: lot.payment_limit_date ? toDateStr(lot.payment_limit_date) : null,
        operation_number: lot.operation_number ?? null,
        contract_date: lot.contract_date ? toDateStr(lot.contract_date) : null,
        contract_number: lot.contract_number ?? null,
        notarial_transfer_date: lot.notarial_transfer_date ? toDateStr(lot.notarial_transfer_date) : null,
        observations: lot.observations ?? null,
        block: lot.block,
        number: lot.number,
        area: toNum(lot.area),
        price: toNum(lot.price),
        ...overrides,
    });

    const updateLot = (lot: Lot, payload: LotPayload) => {
        setSavingLotId(lot.id);
        setEdited((prev) => {
            const next = { ...prev };
            delete next[lot.id];
            return next;
        });
        router.put(`/inmopro/lots/${lot.id}`, payload, {
            preserveScroll: true,
            onFinish: () => setSavingLotId(null),
        });
    };

    const buildRowPayloadForSave = (lot: Lot): LotPayload => buildPayload(lot, {
        client_id: edited[lot.id]?.client_id ?? null,
        client_name: getCellValue(lot, 'client_name').trim() || null,
        client_dni: getCellValue(lot, 'client_dni').trim() || null,
        client_phone: getCellValue(lot, 'client_phone').trim() || null,
        advisor_id: (edited[lot.id]?.advisor_id ?? lot.advisor?.id ?? null) as number | null,
        area: toNum(getCellValue(lot, 'area')) ?? toNum(lot.area),
        price: toNum(getCellValue(lot, 'price')) ?? toNum(lot.price),
        advance: toNum(getCellValue(lot, 'advance')) ?? toNum(lot.advance),
        remaining_balance: toNum(getCellValue(lot, 'remaining_balance')) ?? toNum(lot.remaining_balance),
        payment_limit_date: getCellValue(lot, 'payment_limit_date') ? toDateStr(getCellValue(lot, 'payment_limit_date')) : (lot.payment_limit_date ? toDateStr(lot.payment_limit_date) : null),
        operation_number: getCellValue(lot, 'operation_number').trim() || (lot.operation_number ?? null),
        contract_date: getCellValue(lot, 'contract_date') ? toDateStr(getCellValue(lot, 'contract_date')) : (lot.contract_date ? toDateStr(lot.contract_date) : null),
        contract_number: getCellValue(lot, 'contract_number').trim() || (lot.contract_number ?? null),
        notarial_transfer_date: getCellValue(lot, 'notarial_transfer_date') ? toDateStr(getCellValue(lot, 'notarial_transfer_date')) : (lot.notarial_transfer_date ? toDateStr(lot.notarial_transfer_date) : null),
        observations: getCellValue(lot, 'observations').trim() || (lot.observations ?? null),
    });

    const getCellValue = (lot: Lot, field: keyof Lot | 'advisor_name'): string => {
        if (field === 'advisor_name') {
            return (edited[lot.id]?.advisor_name ?? lot.advisor?.name ?? '') as string;
        }
        const e = edited[lot.id];
        if (e && field in e) {
            const v = e[field as string];
            return v != null && v !== '' ? String(v) : '';
        }
        const raw = lot[field as keyof Lot];
        return raw != null && raw !== '' ? String(raw) : '';
    };

    const setCellEdit = (lot: Lot, field: string, value: string | number | null) => {
        setEdited((prev) => ({ ...prev, [lot.id]: { ...prev[lot.id], [field]: value } }));
    };

    const inputClass =
        'w-full min-w-0 border-0 bg-transparent px-1 py-0.5 text-xs outline-none focus:bg-white focus:ring-1 focus:ring-emerald-400 disabled:opacity-60';
    const selectClass =
        'w-full min-w-0 max-w-[120px] border-0 bg-transparent px-1 py-0.5 text-xs outline-none focus:bg-white focus:ring-1 focus:ring-emerald-400 disabled:opacity-60';
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Proyectos', href: '/inmopro/projects' },
        { title: project.name, href: `/inmopro/projects/${project.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${project.name} - Inmopro`} />
            <div className="flex flex-col gap-4 p-4 md:p-6" style={{ minHeight: 'calc(100vh - 8rem)' }}>
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">{project.name}</h1>
                        {project.location && (
                            <p className="mt-1 flex items-center gap-1.5 text-sm text-slate-500">
                                <MapPin className="h-4 w-4 text-slate-400" />
                                {project.location}
                            </p>
                        )}
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/inmopro/lots?project_id=${project.id}`}>
                                <LayoutGrid className="h-4 w-4" />
                                Ver inventario
                            </Link>
                        </Button>
                        <Button size="sm" asChild>
                            <Link href={`/inmopro/lots/create?project_id=${project.id}`}>
                                <Plus className="h-4 w-4" />
                                Nuevo lote
                            </Link>
                        </Button>
                        <Button size="sm" asChild>
                            <Link href={`/inmopro/projects/${project.id}/edit`}>
                                <Pencil className="h-4 w-4" />
                                Editar proyecto
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="flex flex-wrap items-center gap-3 text-xs text-slate-500">
                    <span className="tabular-nums font-medium text-slate-700">
                        {project.lots?.length ?? 0} lotes
                    </span>
                    <span>·</span>
                    <span>Manzanas: {project.blocks?.length ? project.blocks.join(', ') : '—'}</span>
                </div>

                {errors?.client && (
                    <Alert variant="destructive">
                        <AlertDescription>{errors.client}</AlertDescription>
                    </Alert>
                )}

                {project.lots && project.lots.length > 0 && (
                    <Card className="flex min-h-0 flex-1 flex-col">
                        <CardContent className="flex min-h-0 flex-1 flex-col p-0">
                            <div className="inline-block max-h-[calc(100vh-11rem)] max-w-full overflow-auto">
                                <table className="w-full min-w-[1200px] border-collapse text-xs">
                                    <thead className="sticky top-0 z-10 border-b border-slate-300 bg-[#f3f4f6]">
                                        <tr>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">Manzana</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">Número</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">Área</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">Precio</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">Estado</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">Nombre cliente</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">DNI</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">Teléfono</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">Asesor</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">Adelanto</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">Monto rest.</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">F. límite</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">N° op.</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">F. contrato</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">Nº contrato</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">F. escritura</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-left font-semibold text-slate-700">Observ.</th>
                                            <th className="border border-slate-300 px-1.5 py-1 text-center font-semibold text-slate-700">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {project.lots.map((lot) => {
                                            const isSaving = savingLotId === lot.id;
                                            const statusCode = lot.status?.code ?? 'LIBRE';
                                            const canEdit = statusCode === 'RESERVADO' || statusCode === 'TRANSFERIDO';
                                            const rowBg =
                                                isSaving
                                                    ? 'bg-amber-100'
                                                    : statusCode === 'LIBRE'
                                                        ? 'bg-emerald-50'
                                                        : statusCode === 'RESERVADO'
                                                            ? 'bg-amber-50'
                                                            : 'bg-slate-100';
                                            return (
                                                <tr key={lot.id} className={rowBg}>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle text-slate-700">
                                                        {lot.block}
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle tabular-nums text-slate-700">
                                                        {lot.number}
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle">
                                                        <input
                                                            type="number"
                                                            min={0}
                                                            step={0.01}
                                                            value={getCellValue(lot, 'area')}
                                                            disabled={isSaving || !canEdit}
                                                            onChange={(e) => setCellEdit(lot, 'area', e.target.value ? Number(e.target.value) : null)}
                                                            className={inputClass}
                                                            style={{ minWidth: '4rem' }}
                                                        />
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle">
                                                        <input
                                                            type="number"
                                                            min={0}
                                                            step={0.01}
                                                            value={getCellValue(lot, 'price')}
                                                            disabled={isSaving || !canEdit}
                                                            onChange={(e) => setCellEdit(lot, 'price', e.target.value ? Number(e.target.value) : null)}
                                                            className={inputClass}
                                                            style={{ minWidth: '5rem' }}
                                                        />
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle">
                                                        <select
                                                            value={lot.status?.id ?? ''}
                                                            disabled={isSaving}
                                                            onChange={(e) => updateLot(lot, buildPayload(lot, { lot_status_id: Number(e.target.value) }))}
                                                            className={selectClass}
                                                        >
                                                            {lotStatuses.map((s) => (
                                                                <option key={s.id} value={s.id}>{s.name}</option>
                                                            ))}
                                                        </select>
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle relative">
                                                        <input
                                                            type="text"
                                                            value={getCellValue(lot, 'client_name')}
                                                            disabled={isSaving || !canEdit}
                                                            onChange={(e) => {
                                                                const v = e.target.value;
                                                                setCellEdit(lot, 'client_name', v);
                                                                clientSearch.setOpenKey(`${lot.id}_client_name`);
                                                                advisorSearch.setOpenKey(null);
                                                                clientSearch.triggerSearch(v, `${lot.id}_client_name`);
                                                            }}
                                                            onFocus={() => {
                                                                const v = getCellValue(lot, 'client_name');
                                                                clientSearch.setOpenKey(`${lot.id}_client_name`);
                                                                advisorSearch.setOpenKey(null);
                                                                clientSearch.triggerSearch(v, `${lot.id}_client_name`);
                                                            }}
                                                            onBlur={() => {
                                                                setTimeout(() => clientSearch.setOpenKey(null), 180);
                                                                if (clientJustSelectedRef.current?.lotId === lot.id) {
                                                                    clientJustSelectedRef.current = null;
                                                                }
                                                            }}
                                                            className={inputClass}
                                                            style={{ minWidth: '8rem' }}
                                                            placeholder="Buscar por nombre"
                                                        />
                                                        {clientSearch.openKey === `${lot.id}_client_name` && (
                                                            <div className="absolute left-0 top-full z-20 mt-0.5 max-h-40 w-72 overflow-auto rounded border border-slate-200 bg-white shadow-lg">
                                                                {clientSearch.loading ? (
                                                                    <div className="px-2 py-1.5 text-xs text-slate-500">Buscando…</div>
                                                                ) : clientSearch.results.length === 0 ? (
                                                                    <div className="px-2 py-1.5 text-xs text-slate-500">Sin resultados. Se creará cliente al guardar.</div>
                                                                ) : (
                                                                    clientSearch.results.map((c) => (
                                                                        <button
                                                                            key={c.id}
                                                                            type="button"
                                                                            className="flex w-full flex-col gap-0.5 px-2 py-1.5 text-left text-xs hover:bg-slate-100"
                                                                            onMouseDown={(e) => { e.preventDefault(); }}
                                                                            onClick={() => {
                                                                                clientJustSelectedRef.current = { lotId: lot.id };
                                                                                setCellEdit(lot, 'client_id', c.id);
                                                                                setCellEdit(lot, 'client_name', c.name);
                                                                                setCellEdit(lot, 'client_dni', c.dni ?? null);
                                                                                setCellEdit(lot, 'client_phone', c.phone ?? null);
                                                                                clientSearch.setOpenKey(null);
                                                                            }}
                                                                        >
                                                                            <span className="font-medium">{c.name}</span>
                                                                            {(c.dni || c.phone) && (
                                                                                <span className="text-slate-500">{[c.dni, c.phone].filter(Boolean).join(' · ')}</span>
                                                                            )}
                                                                        </button>
                                                                    ))
                                                                )}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle relative">
                                                        <input
                                                            type="text"
                                                            value={getCellValue(lot, 'client_dni')}
                                                            disabled={isSaving || !canEdit}
                                                            onChange={(e) => {
                                                                const v = e.target.value;
                                                                setCellEdit(lot, 'client_dni', v);
                                                                clientSearch.setOpenKey(`${lot.id}_client_dni`);
                                                                advisorSearch.setOpenKey(null);
                                                                clientSearch.triggerSearch(v, `${lot.id}_client_dni`);
                                                            }}
                                                            onFocus={() => {
                                                                const v = getCellValue(lot, 'client_dni');
                                                                clientSearch.setOpenKey(`${lot.id}_client_dni`);
                                                                advisorSearch.setOpenKey(null);
                                                                clientSearch.triggerSearch(v, `${lot.id}_client_dni`);
                                                            }}
                                                            onBlur={() => {
                                                                setTimeout(() => clientSearch.setOpenKey(null), 180);
                                                                if (clientJustSelectedRef.current?.lotId === lot.id) {
                                                                    clientJustSelectedRef.current = null;
                                                                }
                                                            }}
                                                            className={inputClass}
                                                            style={{ minWidth: '6rem' }}
                                                            placeholder="Buscar por DNI"
                                                        />
                                                        {clientSearch.openKey === `${lot.id}_client_dni` && (
                                                            <div className="absolute left-0 top-full z-20 mt-0.5 max-h-40 w-72 overflow-auto rounded border border-slate-200 bg-white shadow-lg">
                                                                {clientSearch.loading ? (
                                                                    <div className="px-2 py-1.5 text-xs text-slate-500">Buscando…</div>
                                                                ) : clientSearch.results.length === 0 ? (
                                                                    <div className="px-2 py-1.5 text-xs text-slate-500">Sin resultados. Se creará cliente al guardar.</div>
                                                                ) : (
                                                                    clientSearch.results.map((c) => (
                                                                        <button
                                                                            key={c.id}
                                                                            type="button"
                                                                            className="flex w-full flex-col gap-0.5 px-2 py-1.5 text-left text-xs hover:bg-slate-100"
                                                                            onMouseDown={(e) => { e.preventDefault(); }}
                                                                            onClick={() => {
                                                                                clientJustSelectedRef.current = { lotId: lot.id };
                                                                                setCellEdit(lot, 'client_id', c.id);
                                                                                setCellEdit(lot, 'client_name', c.name);
                                                                                setCellEdit(lot, 'client_dni', c.dni ?? null);
                                                                                setCellEdit(lot, 'client_phone', c.phone ?? null);
                                                                                clientSearch.setOpenKey(null);
                                                                            }}
                                                                        >
                                                                            <span className="font-medium">{c.name}</span>
                                                                            {(c.dni || c.phone) && (
                                                                                <span className="text-slate-500">DNI: {c.dni ?? '—'} · {c.phone ?? '—'}</span>
                                                                            )}
                                                                        </button>
                                                                    ))
                                                                )}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle">
                                                        <input
                                                            type="text"
                                                            value={getCellValue(lot, 'client_phone')}
                                                            disabled={isSaving || !canEdit}
                                                            onChange={(e) => setCellEdit(lot, 'client_phone', e.target.value)}
                                                            className={inputClass}
                                                            style={{ minWidth: '6rem' }}
                                                            placeholder="Opcional"
                                                        />
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle relative">
                                                        <input
                                                            type="text"
                                                            value={advisorSearch.openKey === `${lot.id}_advisor` ? (advisorSearchTerm[lot.id] ?? getCellValue(lot, 'advisor_name')) : getCellValue(lot, 'advisor_name')}
                                                            disabled={isSaving || !canEdit}
                                                            onChange={(e) => {
                                                                const v = e.target.value;
                                                                setAdvisorSearchTerm((prev) => ({ ...prev, [lot.id]: v }));
                                                                advisorSearch.setOpenKey(`${lot.id}_advisor`);
                                                                clientSearch.setOpenKey(null);
                                                                advisorSearch.triggerSearch(v, `${lot.id}_advisor`);
                                                            }}
                                                            onFocus={() => {
                                                                const v = getCellValue(lot, 'advisor_name');
                                                                setAdvisorSearchTerm((prev) => ({ ...prev, [lot.id]: v }));
                                                                advisorSearch.setOpenKey(`${lot.id}_advisor`);
                                                                clientSearch.setOpenKey(null);
                                                                advisorSearch.triggerSearch(v, `${lot.id}_advisor`);
                                                            }}
                                                            onBlur={() => {
                                                                setTimeout(() => {
                                                                    advisorSearch.setOpenKey(null);
                                                                    setAdvisorSearchTerm((prev) => {
                                                                        const next = { ...prev };
                                                                        delete next[lot.id];
                                                                        return next;
                                                                    });
                                                                }, 180);
                                                            }}
                                                            className={inputClass}
                                                            style={{ minWidth: '9rem' }}
                                                            placeholder="Buscar asesor"
                                                        />
                                                        {advisorSearch.openKey === `${lot.id}_advisor` && (
                                                            <div className="absolute left-0 top-full z-20 mt-0.5 max-h-40 w-72 overflow-auto rounded border border-slate-200 bg-white shadow-lg">
                                                                {advisorSearch.loading ? (
                                                                    <div className="px-2 py-1.5 text-xs text-slate-500">Buscando…</div>
                                                                ) : advisorSearch.results.length === 0 ? (
                                                                    <div className="px-2 py-1.5 text-xs text-slate-500">Sin resultados.</div>
                                                                ) : (
                                                                    <>
                                                                        <button
                                                                            type="button"
                                                                            className="flex w-full px-2 py-1.5 text-left text-xs hover:bg-slate-100"
                                                                            onMouseDown={(e) => { e.preventDefault(); }}
                                                                            onClick={() => {
                                                                                setCellEdit(lot, 'advisor_id', null);
                                                                                setCellEdit(lot, 'advisor_name', '');
                                                                                setAdvisorSearchTerm((prev) => ({ ...prev, [lot.id]: '' }));
                                                                                advisorSearch.setOpenKey(null);
                                                                            }}
                                                                        >
                                                                            — Ninguno
                                                                        </button>
                                                                        {advisorSearch.results.map((a) => (
                                                                            <button
                                                                                key={a.id}
                                                                                type="button"
                                                                                className="flex w-full px-2 py-1.5 text-left text-xs hover:bg-slate-100"
                                                                                onMouseDown={(e) => { e.preventDefault(); }}
                                                                                onClick={() => {
                                                                                    setCellEdit(lot, 'advisor_id', a.id);
                                                                                    setCellEdit(lot, 'advisor_name', a.name);
                                                                                    setAdvisorSearchTerm((prev) => ({ ...prev, [lot.id]: a.name }));
                                                                                    advisorSearch.setOpenKey(null);
                                                                                }}
                                                                            >
                                                                                {a.name}
                                                                            </button>
                                                                        ))}
                                                                    </>
                                                                )}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle">
                                                        <input
                                                            type="number"
                                                            min={0}
                                                            step={0.01}
                                                            value={getCellValue(lot, 'advance')}
                                                            disabled={isSaving || !canEdit}
                                                            onChange={(e) => setCellEdit(lot, 'advance', e.target.value ? Number(e.target.value) : null)}
                                                            className={inputClass}
                                                            style={{ minWidth: '5rem' }}
                                                        />
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle">
                                                        <input
                                                            type="number"
                                                            min={0}
                                                            step={0.01}
                                                            value={getCellValue(lot, 'remaining_balance')}
                                                            disabled={isSaving || !canEdit}
                                                            onChange={(e) => setCellEdit(lot, 'remaining_balance', e.target.value ? Number(e.target.value) : null)}
                                                            className={inputClass}
                                                            style={{ minWidth: '5rem' }}
                                                        />
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle">
                                                        <input
                                                            type="date"
                                                            value={toDateStr(lot.payment_limit_date)}
                                                            disabled={isSaving || !canEdit}
                                                            onChange={(e) => setCellEdit(lot, 'payment_limit_date', e.target.value || null)}
                                                            className={inputClass}
                                                            style={{ minWidth: '7rem' }}
                                                        />
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle">
                                                        <input
                                                            type="text"
                                                            value={getCellValue(lot, 'operation_number')}
                                                            disabled={isSaving || !canEdit}
                                                            onChange={(e) => setCellEdit(lot, 'operation_number', e.target.value)}
                                                            className={inputClass}
                                                            style={{ minWidth: '5rem' }}
                                                        />
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle">
                                                        <input
                                                            type="date"
                                                            value={toDateStr(lot.contract_date)}
                                                            disabled={isSaving || !canEdit}
                                                            onChange={(e) => setCellEdit(lot, 'contract_date', e.target.value || null)}
                                                            className={inputClass}
                                                            style={{ minWidth: '7rem' }}
                                                        />
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle">
                                                        <input
                                                            type="text"
                                                            value={getCellValue(lot, 'contract_number')}
                                                            disabled={isSaving || !canEdit}
                                                            onChange={(e) => setCellEdit(lot, 'contract_number', e.target.value)}
                                                            className={inputClass}
                                                            style={{ minWidth: '6rem' }}
                                                        />
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle">
                                                        <input
                                                            type="date"
                                                            value={toDateStr(lot.notarial_transfer_date)}
                                                            disabled={isSaving || !canEdit}
                                                            onChange={(e) => setCellEdit(lot, 'notarial_transfer_date', e.target.value || null)}
                                                            className={inputClass}
                                                            style={{ minWidth: '7rem' }}
                                                        />
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle">
                                                        <input
                                                            type="text"
                                                            value={getCellValue(lot, 'observations')}
                                                            disabled={isSaving || !canEdit}
                                                            onChange={(e) => setCellEdit(lot, 'observations', e.target.value)}
                                                            className={inputClass}
                                                            style={{ minWidth: '8rem' }}
                                                            title={lot.observations ?? ''}
                                                        />
                                                    </td>
                                                    <td className="border border-slate-200 px-1 py-0.5 align-middle">
                                                        <div className="flex items-center justify-center gap-0.5">
                                                            {canEdit && (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="icon"
                                                                    className="h-6 w-6"
                                                                    disabled={isSaving}
                                                                    onClick={() => updateLot(lot, buildRowPayloadForSave(lot))}
                                                                    title="Guardar cambios"
                                                                >
                                                                    <Save className="h-3.5 w-3.5" />
                                                                </Button>
                                                            )}
                                                            <Button variant="ghost" size="icon" className="h-6 w-6" asChild>
                                                                <Link href={`/inmopro/lots/${lot.id}`} title="Ver detalle">
                                                                    <Eye className="h-3.5 w-3.5" />
                                                                </Link>
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
