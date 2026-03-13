import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import Pagination, { type PaginationLink } from '@/components/pagination';
import type { BreadcrumbItem } from '@/types';

type CashAccount = { id: number; name: string; type: string };
type Installment = {
    id: number;
    sequence: number;
    due_date: string;
    amount: string;
    paid_amount: string;
    status: string;
};
type Payment = {
    id: number;
    amount: string;
    paid_at: string;
    payment_method: string;
    cash_account?: { name: string } | null;
};
type LotItem = {
    id: number;
    block: string;
    number: number;
    price: string;
    remaining_balance: string | null;
    total_paid: number;
    overdue_installments: number;
    project?: { name: string } | null;
    client?: { name: string } | null;
    installments: Installment[];
    payments: Payment[];
};

export default function AccountsReceivable({
    lots,
    cashAccounts,
    summary,
}: {
    lots: { data: LotItem[]; links: PaginationLink[] };
    cashAccounts: CashAccount[];
    summary: { portfolio: number; collected: number; pending: number; overdueInstallments: number };
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Cuentas por cobrar', href: '/inmopro/accounts-receivable' },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cuentas por cobrar - Inmopro" />
            <div className="space-y-6 p-4 md:p-6">
                <div className="grid gap-4 md:grid-cols-4">
                    <Metric label="Portafolio" value={summary.portfolio} />
                    <Metric label="Cobrado" value={summary.collected} tone="emerald" />
                    <Metric label="Pendiente" value={summary.pending} tone="amber" />
                    <Metric label="Cuotas vencidas" value={summary.overdueInstallments} raw />
                </div>

                {lots.data.map((lot) => (
                    <LotCard key={lot.id} lot={lot} cashAccounts={cashAccounts} />
                ))}

                <div className="rounded-3xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                    <Pagination links={lots.links} />
                </div>
            </div>
        </AppLayout>
    );
}

function Metric({
    label,
    value,
    tone = 'slate',
    raw = false,
}: {
    label: string;
    value: number;
    tone?: 'slate' | 'emerald' | 'amber';
    raw?: boolean;
}) {
    const tones = {
        slate: 'text-slate-900',
        emerald: 'text-emerald-600',
        amber: 'text-amber-600',
    };

    return (
        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-xs font-bold uppercase tracking-wide text-slate-500">{label}</p>
            <p className={`mt-3 text-2xl font-black ${tones[tone]}`}>
                {raw ? value.toLocaleString() : `S/ ${value.toLocaleString()}`}
            </p>
        </div>
    );
}

function LotCard({ lot, cashAccounts }: { lot: LotItem; cashAccounts: CashAccount[] }) {
    const installmentForm = useForm({ due_date: '', amount: '', notes: '' });
    const paymentForm = useForm({
        lot_installment_id: '',
        cash_account_id: '',
        amount: '',
        paid_at: '',
        payment_method: 'TRANSFERENCIA',
        reference: '',
        notes: '',
    });

    return (
        <div className="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
            <div className="mb-5 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h2 className="text-lg font-black text-slate-900">
                        Lote {lot.block}-{lot.number}
                    </h2>
                    <p className="text-sm text-slate-500">
                        {lot.client?.name ?? 'Sin cliente'} · {lot.project?.name ?? 'Sin proyecto'}
                    </p>
                </div>
                <div className="flex gap-4 text-sm font-bold">
                    <span className="text-emerald-600">Cobrado: S/ {lot.total_paid.toLocaleString()}</span>
                    <span className="text-amber-600">
                        Saldo: S/ {Number(lot.remaining_balance ?? 0).toLocaleString()}
                    </span>
                </div>
            </div>

            <div className="grid gap-6 xl:grid-cols-2">
                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        installmentForm.post(`/inmopro/lots/${lot.id}/installments`, {
                            preserveScroll: true,
                            onSuccess: () => installmentForm.reset(),
                        });
                    }}
                    className="space-y-3 rounded-2xl border border-slate-100 bg-slate-50 p-4"
                >
                    <h3 className="text-sm font-black uppercase text-slate-700">Nueva cuota</h3>
                    <input
                        type="date"
                        value={installmentForm.data.due_date}
                        onChange={(event) => installmentForm.setData('due_date', event.target.value)}
                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none"
                    />
                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="Monto"
                        value={installmentForm.data.amount}
                        onChange={(event) => installmentForm.setData('amount', event.target.value)}
                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none"
                    />
                    <button type="submit" className="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white">
                        Guardar cuota
                    </button>
                </form>

                <form
                    onSubmit={(event) => {
                        event.preventDefault();
                        paymentForm.post(`/inmopro/lots/${lot.id}/payments`, {
                            preserveScroll: true,
                            onSuccess: () => paymentForm.reset('amount', 'reference', 'notes'),
                        });
                    }}
                    className="space-y-3 rounded-2xl border border-slate-100 bg-slate-50 p-4"
                >
                    <h3 className="text-sm font-black uppercase text-slate-700">Registrar pago</h3>
                    <select
                        value={paymentForm.data.lot_installment_id}
                        onChange={(event) => paymentForm.setData('lot_installment_id', event.target.value)}
                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none"
                    >
                        <option value="">Sin cuota especifica</option>
                        {lot.installments.map((installment) => (
                            <option key={installment.id} value={installment.id}>
                                Cuota {installment.sequence} · {installment.status}
                            </option>
                        ))}
                    </select>
                    <select
                        value={paymentForm.data.cash_account_id}
                        onChange={(event) => paymentForm.setData('cash_account_id', event.target.value)}
                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none"
                    >
                        <option value="">Sin cuenta</option>
                        {cashAccounts.map((account) => (
                            <option key={account.id} value={account.id}>
                                {account.name} · {account.type}
                            </option>
                        ))}
                    </select>
                    <input
                        type="number"
                        min="0"
                        step="0.01"
                        placeholder="Monto"
                        value={paymentForm.data.amount}
                        onChange={(event) => paymentForm.setData('amount', event.target.value)}
                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none"
                    />
                    <input
                        type="date"
                        value={paymentForm.data.paid_at}
                        onChange={(event) => paymentForm.setData('paid_at', event.target.value)}
                        className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm outline-none"
                    />
                    <button type="submit" className="rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white">
                        Registrar pago
                    </button>
                </form>
            </div>

            <div className="mt-6 grid gap-4 xl:grid-cols-2">
                <div className="rounded-2xl border border-slate-100 p-4">
                    <h3 className="mb-3 text-sm font-black uppercase text-slate-700">Cronograma</h3>
                    <div className="space-y-2">
                        {lot.installments.length === 0 ? (
                            <p className="text-sm text-slate-500">Sin cuotas registradas.</p>
                        ) : (
                            lot.installments.map((installment) => (
                                <div key={installment.id} className="flex items-center justify-between text-sm">
                                    <span>
                                        Cuota {installment.sequence} · {installment.due_date}
                                    </span>
                                    <span className="font-bold">
                                        {installment.status} · S/ {Number(installment.amount).toLocaleString()}
                                    </span>
                                </div>
                            ))
                        )}
                    </div>
                </div>
                <div className="rounded-2xl border border-slate-100 p-4">
                    <h3 className="mb-3 text-sm font-black uppercase text-slate-700">Pagos</h3>
                    <div className="space-y-2">
                        {lot.payments.length === 0 ? (
                            <p className="text-sm text-slate-500">Sin pagos registrados.</p>
                        ) : (
                            lot.payments.slice(0, 5).map((payment) => (
                                <div key={payment.id} className="flex items-center justify-between text-sm">
                                    <span>
                                        {payment.paid_at} · {payment.payment_method}
                                    </span>
                                    <span className="font-bold text-emerald-600">
                                        S/ {Number(payment.amount).toLocaleString()}
                                    </span>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </div>
    );
}
