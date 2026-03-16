import { Head, router, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { ProjectLotsTable } from './project-lots-table';
import { ProjectShowHeader } from './project-show-header';
import type { Advisor, Client, Lot, LotPayload, PageProps } from './show-types';
import { toDateStr, toNum } from './show-utils';
import {
    SEARCH_CACHE_MAX,
    SEARCH_DEBOUNCE_MS,
    SEARCH_MIN_CHARS,
    searchAdvisors,
    searchClients,
    useSearchableSelect,
} from './use-searchable-select';

export default function ProjectsShow({ project, lotStatuses }: PageProps) {
    const { errors } = usePage<PageProps>().props;
    const [savingLotId, setSavingLotId] = useState<number | null>(null);
    const [edited, setEdited] = useState<Record<number, Partial<Record<string, string | number | null>>>>({});
    const [advisorSearchTerm, setAdvisorSearchTerm] = useState<Record<number, string>>({});
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

    const calculateRemainingBalance = (price: string | number | null | undefined, advance: string | number | null | undefined): number | null => {
        const normalizedPrice = toNum(price);
        const normalizedAdvance = toNum(advance) ?? 0;

        if (normalizedPrice === null) {
            return null;
        }

        return Number((normalizedPrice - normalizedAdvance).toFixed(2));
    };

    const buildPayload = (lot: Lot, overrides: Partial<LotPayload>): LotPayload => ({
        lot_status_id: lot.status?.id ?? lotStatuses[0]?.id ?? 0,
        client_id: overrides.client_id !== undefined ? overrides.client_id : (lot.client?.id ?? null),
        advisor_id: overrides.advisor_id !== undefined ? overrides.advisor_id : (lot.advisor?.id ?? null),
        client_name: lot.client_name ?? lot.client?.name ?? null,
        client_dni: lot.client_dni ?? lot.client?.dni ?? null,
        client_phone: lot.client_phone ?? lot.client?.phone ?? null,
        advance: toNum(lot.advance),
        remaining_balance: calculateRemainingBalance(lot.price, lot.advance),
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

    const getCellValue = (lot: Lot, field: keyof Lot | 'advisor_name'): string => {
        if (field === 'advisor_name') {
            return (edited[lot.id]?.advisor_name ?? lot.advisor?.name ?? '') as string;
        }

        const editedRow = edited[lot.id];

        if (editedRow && field in editedRow) {
            const value = editedRow[field as string];
            return value != null && value !== '' ? String(value) : '';
        }

        const rawValue = lot[field as keyof Lot];
        return rawValue != null && rawValue !== '' ? String(rawValue) : '';
    };

    const setCellEdit = (lot: Lot, field: string, value: string | number | null) => {
        setEdited((prev) => ({ ...prev, [lot.id]: { ...prev[lot.id], [field]: value } }));
    };

    const buildRowPayloadForSave = (lot: Lot): LotPayload =>
        buildPayload(lot, {
            client_id:
                typeof (edited[lot.id]?.client_id ?? lot.client?.id ?? null) === 'string'
                    ? toNum(edited[lot.id]?.client_id as string)
                    : ((edited[lot.id]?.client_id ?? lot.client?.id ?? null) as number | null),
            client_name: getCellValue(lot, 'client_name').trim() || null,
            client_dni: getCellValue(lot, 'client_dni').trim() || null,
            client_phone: getCellValue(lot, 'client_phone').trim() || null,
            advisor_id:
                typeof (edited[lot.id]?.advisor_id ?? lot.advisor?.id ?? null) === 'string'
                    ? toNum(edited[lot.id]?.advisor_id as string)
                    : ((edited[lot.id]?.advisor_id ?? lot.advisor?.id ?? null) as number | null),
            area: toNum(getCellValue(lot, 'area')) ?? toNum(lot.area),
            price: toNum(getCellValue(lot, 'price')) ?? toNum(lot.price),
            advance: toNum(getCellValue(lot, 'advance')) ?? toNum(lot.advance),
            remaining_balance: calculateRemainingBalance(
                toNum(getCellValue(lot, 'price')) ?? toNum(lot.price),
                toNum(getCellValue(lot, 'advance')) ?? toNum(lot.advance)
            ),
            payment_limit_date: getCellValue(lot, 'payment_limit_date')
                ? toDateStr(getCellValue(lot, 'payment_limit_date'))
                : (lot.payment_limit_date ? toDateStr(lot.payment_limit_date) : null),
            operation_number: getCellValue(lot, 'operation_number').trim() || (lot.operation_number ?? null),
            contract_date: getCellValue(lot, 'contract_date')
                ? toDateStr(getCellValue(lot, 'contract_date'))
                : (lot.contract_date ? toDateStr(lot.contract_date) : null),
            contract_number: getCellValue(lot, 'contract_number').trim() || (lot.contract_number ?? null),
            notarial_transfer_date: getCellValue(lot, 'notarial_transfer_date')
                ? toDateStr(getCellValue(lot, 'notarial_transfer_date'))
                : (lot.notarial_transfer_date ? toDateStr(lot.notarial_transfer_date) : null),
            observations: getCellValue(lot, 'observations').trim() || (lot.observations ?? null),
        });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Proyectos', href: '/inmopro/projects' },
        { title: project.name, href: `/inmopro/projects/${project.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${project.name} - Inmopro`} />
            <div className="flex flex-col gap-4 p-4 md:p-6" style={{ minHeight: 'calc(100vh - 8rem)' }}>
                <ProjectShowHeader project={project} clientError={errors?.client} />

                <ProjectLotsTable
                    project={project}
                    lotStatuses={lotStatuses}
                    savingLotId={savingLotId}
                    edited={edited}
                    clientSearch={clientSearch}
                    advisorSearch={advisorSearch}
                    advisorSearchTerm={advisorSearchTerm}
                    setAdvisorSearchTerm={setAdvisorSearchTerm}
                    clientJustSelectedRef={clientJustSelectedRef}
                    getCellValue={getCellValue}
                    setCellEdit={setCellEdit}
                    buildPayload={buildPayload}
                    buildRowPayloadForSave={buildRowPayloadForSave}
                    updateLot={updateLot}
                />
            </div>
        </AppLayout>
    );
}
