import { Head, router, useForm } from '@inertiajs/react';
import { FormEvent, useEffect, useState } from 'react';
import { Search, UserPlus, ChevronRight, Plus, Pencil, Receipt, Wallet, KeyRound } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import Pagination, { type PaginationLink } from '@/components/pagination';
import { confirmDelete } from '@/lib/swal';
import type { BreadcrumbItem } from '@/types';

type AdvisorLevel = { id: number; name: string };
type Team = { id: number; name: string; color?: string };
type Advisor = {
    id: number;
    name: string;
    email: string;
    phone: string;
    username: string;
    personal_quota: string;
    team_id?: number;
    advisor_level_id?: number;
    superior_id?: number | null;
    lots_count?: number;
    team?: Team;
    level?: { name: string; color?: string };
    superior?: { name: string };
    memberships?: Membership[];
};
type Payment = {
    id: number;
    amount: string;
    paid_at: string;
    notes?: string | null;
    advisor_membership_installment_id?: number | null;
    cash_account_id?: number | null;
};
type Installment = {
    id: number;
    sequence: number;
    due_date?: string | null;
    amount: string;
    paid_amount: string;
    status: string;
    notes?: string | null;
};
type Membership = {
    id: number;
    advisor_id: number;
    year: number;
    amount: string;
    start_date?: string | null;
    end_date?: string | null;
    advisor?: { id: number; name: string };
    payments?: Payment[];
    installments?: Installment[];
};
type MembershipType = { id: number; name: string; months: number; amount: string };
type CashAccount = { id: number; name: string };
type MembershipDetail = {
    membership: Membership;
    totalPaid: number;
    balanceDue: number;
    isPaid: boolean;
};
type Paginated<T> = { data: T[]; links: PaginationLink[]; current_page: number; last_page: number };

type PageProps = {
    advisors: Paginated<Advisor>;
    advisorLevels: AdvisorLevel[];
    advisorsList: { id: number; name: string }[];
    teams: Team[];
    membershipTypes: MembershipType[];
    cashAccounts: CashAccount[];
    membershipDetail: MembershipDetail | null;
    advisorForModal: Advisor | null;
    openModal: string | null;
    filters: { search?: string };
};

function advisorOwesMembership(advisor: Advisor): boolean {
    const memberships = advisor.memberships ?? [];
    if (memberships.length === 0) return false;
    return memberships.some((m) => {
        const totalPaid = (m.payments ?? []).reduce((sum, p) => sum + Number(p.amount), 0);
        return Number(m.amount) - totalPaid > 0;
    });
}

function membershipToDetail(m: Membership, advisor?: { id: number; name: string }): MembershipDetail {
    const totalPaid = (m.payments ?? []).reduce((sum, p) => sum + Number(p.amount), 0);
    const balanceDue = Math.max(0, Number(m.amount) - totalPaid);
    const membership = { ...m, advisor: m.advisor ?? advisor };
    return { membership, totalPaid, balanceDue, isPaid: balanceDue <= 0 };
}

export default function AdvisorsIndex({
    advisors,
    advisorLevels,
    advisorsList,
    teams,
    membershipTypes,
    cashAccounts,
    membershipDetail,
    advisorForModal,
    openModal,
    filters,
}: PageProps) {
    const [modalCreateAdvisor, setModalCreateAdvisor] = useState(false);
    const [modalEditAdvisor, setModalEditAdvisor] = useState<Advisor | null>(null);
    const [modalCreateMembership, setModalCreateMembership] = useState(false);
    const [modalAdvisorMemberships, setModalAdvisorMemberships] = useState<Advisor | null>(null);
    const [modalAssignMembership, setModalAssignMembership] = useState<Advisor | null>(null);
    const [modalMembershipDetail, setModalMembershipDetail] = useState<MembershipDetail | null>(null);

    useEffect(() => {
        if (openModal === 'create_advisor') setModalCreateAdvisor(true);
        if (openModal === 'edit_advisor' && advisorForModal) setModalEditAdvisor(advisorForModal);
        if (openModal === 'create_membership') setModalCreateMembership(true);
        if (membershipDetail) setModalMembershipDetail(membershipDetail);
    }, [openModal, advisorForModal, membershipDetail]);

    const totalPaid = (m: Membership) => (m.payments ?? []).reduce((sum, p) => sum + Number(p.amount), 0);
    const balanceDue = (m: Membership) => Math.max(0, Number(m.amount) - totalPaid(m));
    const isPaid = (m: Membership) => balanceDue(m) <= 0;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Vendedores', href: '/inmopro/advisors' },
    ];
    const totalAdvisors = advisors.data.length;
    const totalQuota = advisors.data.reduce((sum, advisor) => sum + Number(advisor.personal_quota), 0);
    const membershipsPending = advisors.data.reduce((sum, advisor) => {
        return sum + (advisor.memberships ?? []).filter((membership) => !isPaid(membership)).length;
    }, 0);

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        const form = e.target as HTMLFormElement;
        const q = new FormData(form).get('search') as string;
        router.get('/inmopro/advisors', { ...filters, search: q || undefined }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Vendedores - Inmopro" />
            <div className="space-y-6 p-4">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h2 className="text-2xl font-black uppercase tracking-tight text-slate-800">
                            Estructura Comercial y Membresías
                        </h2>
                        <p className="text-sm italic font-medium text-slate-500">
                            Vendedores, jerarquías y control de membresías anuales.
                        </p>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button onClick={() => setModalCreateAdvisor(true)} className="flex items-center gap-2">
                            <UserPlus className="h-5 w-5" />
                            Nuevo vendedor
                        </Button>
                        <Button onClick={() => setModalCreateMembership(true)} variant="outline" className="flex items-center gap-2">
                            <Plus className="h-5 w-5" />
                            Nueva membresía
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <AdvisorMetric label="Vendedores visibles" value={String(totalAdvisors)} />
                    <AdvisorMetric label="Meta acumulada" value={`S/ ${totalQuota.toLocaleString()}`} tone="emerald" />
                    <AdvisorMetric label="Membresias pendientes" value={String(membershipsPending)} tone="amber" />
                </div>

                <form onSubmit={handleSearch} className="flex gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <div className="relative w-full sm:w-80">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <input
                                    name="search"
                                    type="text"
                                    placeholder="Buscar por nombre o email..."
                                    className="w-full rounded-xl border border-slate-200 bg-slate-50 py-2.5 pl-10 pr-4 font-medium outline-none transition-all focus:ring-2 focus:ring-emerald-500"
                                    defaultValue={filters.search}
                                />
                            </div>
                            <Button type="submit">Buscar</Button>
                </form>

                <div className="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div className="overflow-x-auto">
                        <table className="w-full border-collapse text-left text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/50">
                                    <th className="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-slate-400">Nivel / Vendedor</th>
                                    <th className="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-slate-400">Team</th>
                                    <th className="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-slate-400">Superior</th>
                                    <th className="px-4 py-3 text-[10px] font-black uppercase tracking-widest text-slate-400">Cuota</th>
                                    <th className="px-4 py-3 text-center text-[10px] font-black uppercase tracking-widest text-slate-400">Membresía</th>
                                    <th className="px-4 py-3 text-right text-[10px] font-black uppercase tracking-widest text-slate-400">Acciones</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {advisors.data.map((adv) => (
                                    <tr key={adv.id} className="transition-colors hover:bg-slate-50">
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-2">
                                                <span className="rounded bg-slate-900 px-2 py-0.5 text-[10px] font-black text-white">
                                                    {adv.level?.name ?? '-'}
                                                </span>
                                                <div>
                                                    <p className="font-bold leading-none text-slate-800">{adv.name}</p>
                                                    <p className="text-[10px] text-slate-400">{adv.email}</p>
                                                    <p className="text-[10px] font-semibold text-slate-500">@{adv.username}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3">
                                            <span
                                                className="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-white"
                                                style={{ backgroundColor: adv.team?.color ?? '#0f172a' }}
                                            >
                                                {adv.team?.name ?? 'Sin team'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-slate-700">{adv.superior?.name ?? 'Alta Gerencia'}</td>
                                        <td className="px-4 py-3 font-medium text-slate-700">S/ {Number(adv.personal_quota).toLocaleString()}</td>
                                        <td className="px-4 py-3 text-center">
                                            <span
                                                className={`inline-block rounded px-2.5 py-0.5 text-[10px] font-bold ${
                                                    advisorOwesMembership(adv)
                                                        ? 'bg-amber-100 text-amber-800'
                                                        : 'bg-emerald-100 text-emerald-800'
                                                }`}
                                            >
                                                {advisorOwesMembership(adv) ? 'Debe' : 'Al día'}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 text-right">
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-slate-400 hover:text-slate-900"
                                                onClick={() => setModalAdvisorMemberships(adv)}
                                                title="Ver membresías y pagos"
                                            >
                                                <Wallet className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-slate-400 hover:text-slate-900"
                                                onClick={() => setModalEditAdvisor(adv)}
                                                title="Editar"
                                            >
                                                <Pencil className="h-4 w-4" />
                                            </Button>
                                            <Button variant="ghost" size="icon" className="h-8 w-8" asChild title="Ver perfil">
                                                <a href={`/inmopro/advisors/${adv.id}`}>
                                                    <ChevronRight className="h-4 w-4" />
                                                </a>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                    {advisors.data.length === 0 ? (
                        <div className="py-12 text-center text-slate-500">
                            <Receipt className="mx-auto h-10 w-10" />
                            <p className="mt-2">No hay vendedores. Cree uno o busque con otro criterio.</p>
                        </div>
                    ) : (
                        <div className="border-t border-slate-100 px-4 py-3">
                            <Pagination links={advisors.links} />
                        </div>
                    )}
                </div>

                {/* Modal: Crear vendedor */}
                <CreateAdvisorModal
                    open={modalCreateAdvisor}
                    onOpenChange={setModalCreateAdvisor}
                    advisorLevels={advisorLevels}
                    advisorsList={advisorsList}
                    teams={teams}
                />

                {/* Modal: Editar vendedor */}
                {modalEditAdvisor && (
                    <EditAdvisorModal
                        open={!!modalEditAdvisor}
                        onOpenChange={(open) => !open && setModalEditAdvisor(null)}
                        advisor={modalEditAdvisor}
                        advisorLevels={advisorLevels}
                        advisorsList={advisorsList.filter((a) => a.id !== modalEditAdvisor.id)}
                        teams={teams}
                    />
                )}

                {/* Modal: Nueva membresía */}
                <CreateMembershipModal open={modalCreateMembership} onOpenChange={setModalCreateMembership} advisorsList={advisorsList} />

                {/* Modal: Membresías del asesor (montos por año + pagar) */}
                {modalAdvisorMemberships && (
                    <AdvisorMembershipsModal
                        open={!!modalAdvisorMemberships}
                        onOpenChange={(open) => !open && setModalAdvisorMemberships(null)}
                        advisor={modalAdvisorMemberships}
                        membershipTypes={membershipTypes}
                        onAssignMembership={() => {
                            setModalAssignMembership(modalAdvisorMemberships);
                            setModalAdvisorMemberships(null);
                        }}
                        onPayMembership={(detail) => {
                            setModalAdvisorMemberships(null);
                            setModalMembershipDetail(detail);
                        }}
                        isPaid={isPaid}
                        balanceDue={balanceDue}
                        totalPaid={totalPaid}
                        membershipToDetail={membershipToDetail}
                    />
                )}

                {/* Modal: Asignar membresía a un vendedor */}
                {modalAssignMembership && (
                    <AssignMembershipModal
                        open={!!modalAssignMembership}
                        onOpenChange={(open) => !open && setModalAssignMembership(null)}
                        advisor={modalAssignMembership}
                        membershipTypes={membershipTypes}
                        onSuccess={() => {
                            setModalAssignMembership(null);
                            router.reload();
                        }}
                    />
                )}

                {/* Modal: Detalle membresía + abonos */}
                {modalMembershipDetail && (
                    <MembershipDetailModal
                        open={!!modalMembershipDetail}
                        onOpenChange={(open) => {
                            if (!open) {
                                setModalMembershipDetail(null);
                                router.get('/inmopro/advisors', { ...filters }, { preserveState: true });
                            }
                        }}
                        detail={modalMembershipDetail}
                        cashAccounts={cashAccounts}
                    />
                )}
            </div>
        </AppLayout>
    );
}

function AdvisorMetric({
    label,
    value,
    tone = 'slate',
}: {
    label: string;
    value: string;
    tone?: 'slate' | 'emerald' | 'amber';
}) {
    const tones = {
        slate: 'text-slate-900',
        emerald: 'text-emerald-600',
        amber: 'text-amber-600',
    };

    return (
        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-[10px] font-black uppercase tracking-widest text-slate-400">{label}</p>
            <p className={`mt-3 text-3xl font-black ${tones[tone]}`}>{value}</p>
        </div>
    );
}

function CreateAdvisorModal({
    open,
    onOpenChange,
    advisorLevels,
    advisorsList,
    teams,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    advisorLevels: AdvisorLevel[];
    advisorsList: { id: number; name: string }[];
    teams: Team[];
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        phone: '',
        email: '',
        username: '',
        pin: '',
        team_id: teams[0]?.id ?? 0,
        advisor_level_id: advisorLevels[0]?.id ?? 0,
        superior_id: null as number | null,
        personal_quota: 0,
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/inmopro/advisors', { onSuccess: () => { reset(); onOpenChange(false); } });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Nuevo vendedor</DialogTitle>
                    <DialogDescription>Registre los datos del asesor.</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <Label htmlFor="name">Nombre</Label>
                        <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1" />
                        <InputError message={errors.name} />
                    </div>
                    <div>
                        <Label htmlFor="phone">Teléfono</Label>
                        <Input id="phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} className="mt-1" />
                        <InputError message={errors.phone} />
                    </div>
                    <div>
                        <Label htmlFor="email">Email</Label>
                        <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className="mt-1" />
                        <InputError message={errors.email} />
                    </div>
                    <div>
                        <Label htmlFor="username">Usuario</Label>
                        <Input id="username" value={data.username} onChange={(e) => setData('username', e.target.value)} className="mt-1" />
                        <InputError message={errors.username} />
                    </div>
                    <div>
                        <Label htmlFor="pin">PIN</Label>
                        <Input
                            id="pin"
                            inputMode="numeric"
                            maxLength={6}
                            value={data.pin}
                            onChange={(e) => setData('pin', e.target.value.replace(/\D/g, '').slice(0, 6))}
                            className="mt-1"
                        />
                        <p className="mt-1 text-xs text-slate-500">Debe contener 6 dígitos numéricos.</p>
                        <InputError message={errors.pin} />
                    </div>
                    <div>
                        <Label htmlFor="team_id">Team</Label>
                        <select
                            id="team_id"
                            value={data.team_id}
                            onChange={(e) => setData('team_id', Number(e.target.value))}
                            className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2"
                        >
                            {teams.map((team) => (
                                <option key={team.id} value={team.id}>{team.name}</option>
                            ))}
                        </select>
                        <InputError message={errors.team_id} />
                    </div>
                    <div>
                        <Label htmlFor="advisor_level_id">Nivel</Label>
                        <select
                            id="advisor_level_id"
                            value={data.advisor_level_id}
                            onChange={(e) => setData('advisor_level_id', Number(e.target.value))}
                            className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2"
                        >
                            {advisorLevels.map((l) => (
                                <option key={l.id} value={l.id}>{l.name}</option>
                            ))}
                        </select>
                        <InputError message={errors.advisor_level_id} />
                    </div>
                    <div>
                        <Label htmlFor="superior_id">Superior</Label>
                        <select
                            id="superior_id"
                            value={data.superior_id ?? ''}
                            onChange={(e) => setData('superior_id', e.target.value ? Number(e.target.value) : null)}
                            className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2"
                        >
                            <option value="">— Ninguno —</option>
                            {advisorsList.map((a) => (
                                <option key={a.id} value={a.id}>{a.name}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label htmlFor="personal_quota">Cuota personal</Label>
                        <Input
                            id="personal_quota"
                            type="number"
                            min={0}
                            value={data.personal_quota}
                            onChange={(e) => setData('personal_quota', Number(e.target.value))}
                            className="mt-1"
                        />
                        <InputError message={errors.personal_quota} />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Cancelar</Button>
                        <Button type="submit" disabled={processing}>Guardar</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function EditAdvisorModal({
    open,
    onOpenChange,
    advisor,
    advisorLevels,
    advisorsList,
    teams,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    advisor: Advisor;
    advisorLevels: AdvisorLevel[];
    advisorsList: { id: number; name: string }[];
    teams: Team[];
}) {
    const { data, setData, put, processing, errors } = useForm({
        name: advisor.name,
        phone: advisor.phone,
        email: advisor.email,
        username: advisor.username,
        team_id: advisor.team_id ?? teams[0]?.id ?? 0,
        advisor_level_id: advisor.advisor_level_id ?? advisorLevels[0]?.id ?? 0,
        superior_id: advisor.superior_id ?? (null as number | null),
        personal_quota: Number(advisor.personal_quota),
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(`/inmopro/advisors/${advisor.id}`, { onSuccess: () => onOpenChange(false) });
    };

    const handleResetPin = () => {
        if (!window.confirm(`Se restablecerá el PIN de ${advisor.name} a 123456.`)) {
            return;
        }

        router.post(`/inmopro/advisors/${advisor.id}/reset-pin`, {}, { preserveScroll: true });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Editar vendedor</DialogTitle>
                    <DialogDescription>Modifique los datos del asesor y su usuario de acceso.</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <Label htmlFor="edit-name">Nombre</Label>
                        <Input id="edit-name" value={data.name} onChange={(e) => setData('name', e.target.value)} className="mt-1" />
                        <InputError message={errors.name} />
                    </div>
                    <div>
                        <Label htmlFor="edit-phone">Teléfono</Label>
                        <Input id="edit-phone" value={data.phone} onChange={(e) => setData('phone', e.target.value)} className="mt-1" />
                        <InputError message={errors.phone} />
                    </div>
                    <div>
                        <Label htmlFor="edit-email">Email</Label>
                        <Input id="edit-email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} className="mt-1" />
                        <InputError message={errors.email} />
                    </div>
                    <div>
                        <Label htmlFor="edit-username">Usuario</Label>
                        <Input id="edit-username" value={data.username} onChange={(e) => setData('username', e.target.value)} className="mt-1" />
                        <InputError message={errors.username} />
                    </div>
                    <div>
                        <Label htmlFor="edit-team">Team</Label>
                        <select
                            id="edit-team"
                            value={data.team_id}
                            onChange={(e) => setData('team_id', Number(e.target.value))}
                            className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2"
                        >
                            {teams.map((team) => (
                                <option key={team.id} value={team.id}>{team.name}</option>
                            ))}
                        </select>
                        <InputError message={errors.team_id} />
                    </div>
                    <div>
                        <Label htmlFor="edit-level">Nivel</Label>
                        <select
                            id="edit-level"
                            value={data.advisor_level_id}
                            onChange={(e) => setData('advisor_level_id', Number(e.target.value))}
                            className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2"
                        >
                            {advisorLevels.map((l) => (
                                <option key={l.id} value={l.id}>{l.name}</option>
                            ))}
                        </select>
                        <InputError message={errors.advisor_level_id} />
                    </div>
                    <div>
                        <Label htmlFor="edit-superior">Superior</Label>
                        <select
                            id="edit-superior"
                            value={data.superior_id ?? ''}
                            onChange={(e) => setData('superior_id', e.target.value ? Number(e.target.value) : null)}
                            className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2"
                        >
                            <option value="">— Ninguno —</option>
                            {advisorsList.map((a) => (
                                <option key={a.id} value={a.id}>{a.name}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <Label htmlFor="edit-quota">Cuota personal</Label>
                        <Input
                            id="edit-quota"
                            type="number"
                            min={0}
                            value={data.personal_quota}
                            onChange={(e) => setData('personal_quota', Number(e.target.value))}
                            className="mt-1"
                        />
                        <InputError message={errors.personal_quota} />
                    </div>
                    <div className="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <p className="text-sm font-bold text-slate-800">Reset PIN</p>
                                <p className="mt-1 text-xs text-slate-500">
                                    Restablece el PIN del vendedor a <span className="font-bold text-slate-700">123456</span>.
                                </p>
                            </div>
                            <Button type="button" variant="outline" onClick={handleResetPin} className="shrink-0">
                                <KeyRound className="mr-2 h-4 w-4" />
                                Reset PIN
                            </Button>
                        </div>
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Cancelar</Button>
                        <Button type="submit" disabled={processing}>Actualizar</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function AssignMembershipModal({
    open,
    onOpenChange,
    advisor,
    membershipTypes,
    onSuccess,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    advisor: Advisor;
    membershipTypes: MembershipType[];
    onSuccess: () => void;
}) {
    const today = new Date().toISOString().slice(0, 10);
    const { data, setData, post, processing, errors, reset } = useForm({
        membership_type_id: '',
        start_date: today,
        installments_count: '1',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post(`/inmopro/advisors/${advisor.id}/memberships`, {
            onSuccess: () => {
                reset();
                onOpenChange(false);
                onSuccess();
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Asignar membresía</DialogTitle>
                    <DialogDescription>
                        Asignar una membresía a {advisor.name}. Opcionalmente defina un plan de cuotas.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <Label htmlFor="assign-type">Tipo de membresía</Label>
                        <select
                            id="assign-type"
                            value={data.membership_type_id}
                            onChange={(e) => setData('membership_type_id', e.target.value)}
                            className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2"
                            required
                        >
                            <option value="">— Seleccione —</option>
                            {membershipTypes.map((t) => (
                                <option key={t.id} value={t.id}>
                                    {t.name} – {t.months} meses, S/ {Number(t.amount).toLocaleString('es-PE')}
                                </option>
                            ))}
                        </select>
                        <InputError message={errors.membership_type_id} />
                    </div>
                    <div>
                        <Label htmlFor="assign-start">Fecha de inicio</Label>
                        <Input
                            id="assign-start"
                            type="date"
                            value={data.start_date}
                            onChange={(e) => setData('start_date', e.target.value)}
                            className="mt-1"
                            required
                        />
                        <InputError message={errors.start_date} />
                    </div>
                    <div>
                        <Label htmlFor="assign-installments">Pagar en cuotas (número)</Label>
                        <Input
                            id="assign-installments"
                            type="number"
                            min={1}
                            max={60}
                            value={data.installments_count}
                            onChange={(e) => setData('installments_count', e.target.value)}
                            className="mt-1"
                            placeholder="1 = pago único"
                        />
                        <p className="mt-1 text-xs text-slate-500">Deje 1 para pago único; mayor a 1 crea un plan de cuotas.</p>
                        <InputError message={errors.installments_count} />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Cancelar</Button>
                        <Button type="submit" disabled={processing}>Asignar membresía</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function CreateMembershipModal({
    open,
    onOpenChange,
    advisorsList,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    advisorsList: { id: number; name: string }[];
}) {
    const currentYear = new Date().getFullYear();
    const { data, setData, post, processing, errors, reset } = useForm({
        advisor_id: '',
        year: String(currentYear),
        amount: '',
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/inmopro/advisor-memberships', { onSuccess: () => { reset(); onOpenChange(false); } });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Nueva membresía anual</DialogTitle>
                    <DialogDescription>Vendedor, año y monto total a pagar.</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <Label htmlFor="mem-advisor">Vendedor</Label>
                        <select
                            id="mem-advisor"
                            value={data.advisor_id}
                            onChange={(e) => setData('advisor_id', e.target.value)}
                            className="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2"
                            required
                        >
                            <option value="">— Seleccione —</option>
                            {advisorsList.map((a) => (
                                <option key={a.id} value={a.id}>{a.name}</option>
                            ))}
                        </select>
                        <InputError message={errors.advisor_id} />
                    </div>
                    <div>
                        <Label htmlFor="mem-year">Año</Label>
                        <Input
                            id="mem-year"
                            type="number"
                            min={2020}
                            max={2100}
                            value={data.year}
                            onChange={(e) => setData('year', e.target.value)}
                            className="mt-1"
                            required
                        />
                        <InputError message={errors.year} />
                    </div>
                    <div>
                        <Label htmlFor="mem-amount">Monto anual (S/)</Label>
                        <Input
                            id="mem-amount"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.amount}
                            onChange={(e) => setData('amount', e.target.value)}
                            className="mt-1"
                            required
                        />
                        <InputError message={errors.amount} />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Cancelar</Button>
                        <Button type="submit" disabled={processing}>Crear membresía</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function formatMembershipDate(d: string | null | undefined): string {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('es-PE', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

function isMembershipExpired(m: Membership): boolean {
    if (!m.end_date) return false;
    return new Date(m.end_date) < new Date();
}

function AdvisorMembershipsModal({
    open,
    onOpenChange,
    advisor,
    membershipTypes,
    onAssignMembership,
    onPayMembership,
    isPaid,
    balanceDue,
    totalPaid,
    membershipToDetail,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    advisor: Advisor;
    membershipTypes: MembershipType[];
    onAssignMembership: () => void;
    onPayMembership: (detail: MembershipDetail) => void;
    isPaid: (m: Membership) => boolean;
    balanceDue: (m: Membership) => number;
    totalPaid: (m: Membership) => number;
    membershipToDetail: (m: Membership, advisor?: { id: number; name: string }) => MembershipDetail;
}) {
    const memberships = (advisor.memberships ?? []).slice().sort((a, b) => {
        const endA = a.end_date ? new Date(a.end_date).getTime() : a.year * 1000;
        const endB = b.end_date ? new Date(b.end_date).getTime() : b.year * 1000;
        return endB - endA;
    });

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
                <DialogHeader>
                    <DialogTitle>Membresías – {advisor.name}</DialogTitle>
                    <DialogDescription>
                        Control de vencimiento y pago. Use &quot;Pagar membresía&quot; para registrar abonos.
                    </DialogDescription>
                </DialogHeader>
                {membershipTypes.length > 0 && (
                    <div className="flex justify-end">
                        <Button type="button" size="sm" onClick={onAssignMembership}>
                            Asignar membresía
                        </Button>
                    </div>
                )}
                <div className="overflow-x-auto rounded-lg border border-slate-200">
                    {memberships.length === 0 ? (
                        <div className="py-8 text-center text-sm text-slate-500">
                            No tiene membresías registradas.
                        </div>
                    ) : (
                        <table className="w-full text-left text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/50">
                                    <th className="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400">Inicio</th>
                                    <th className="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400">Vence</th>
                                    <th className="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400">Monto</th>
                                    <th className="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400">Cuotas</th>
                                    <th className="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400">Abonado</th>
                                    <th className="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400">Pendiente</th>
                                    <th className="px-4 py-2 text-[10px] font-black uppercase tracking-widest text-slate-400">Estado</th>
                                    <th className="px-4 py-2 text-right text-[10px] font-black uppercase tracking-widest text-slate-400">Acción</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-slate-100">
                                {memberships.map((mem) => {
                                    const paid = isPaid(mem);
                                    const paidSum = totalPaid(mem);
                                    const due = balanceDue(mem);
                                    const expired = isMembershipExpired(mem);
                                    const installments = mem.installments ?? [];
                                    const paidInstallments = installments.filter((i) => i.status === 'PAGADA').length;
                                    const cuotasLabel = installments.length > 0 ? `${paidInstallments}/${installments.length} pagadas` : '—';
                                    return (
                                        <tr key={mem.id} className="hover:bg-slate-50">
                                            <td className="px-4 py-2 text-slate-600">{formatMembershipDate(mem.start_date)}</td>
                                            <td className="px-4 py-2 text-slate-600">{formatMembershipDate(mem.end_date)}</td>
                                            <td className="px-4 py-2">S/ {Number(mem.amount).toLocaleString('es-PE')}</td>
                                            <td className="px-4 py-2 text-slate-600">{cuotasLabel}</td>
                                            <td className="px-4 py-2 text-emerald-700">S/ {paidSum.toLocaleString('es-PE')}</td>
                                            <td className="px-4 py-2 text-amber-600">S/ {due.toLocaleString('es-PE')}</td>
                                            <td className="px-4 py-2">
                                                {expired && (
                                                    <span className="mr-1 inline-block rounded px-2 py-0.5 text-[10px] font-bold bg-red-100 text-red-800">
                                                        Vencida
                                                    </span>
                                                )}
                                                <span
                                                    className={`inline-block rounded px-2 py-0.5 text-[10px] font-bold ${
                                                        paid ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'
                                                    }`}
                                                >
                                                    {paid ? 'Al día' : 'Debe'}
                                                </span>
                                            </td>
                                            <td className="px-4 py-2 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => onPayMembership(membershipToDetail(mem, { id: advisor.id, name: advisor.name }))}
                                                >
                                                    Pagar membresía
                                                </Button>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    )}
                </div>
                <DialogFooter>
                    <Button type="button" onClick={() => onOpenChange(false)}>Cerrar</Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

function MembershipDetailModal({
    open,
    onOpenChange,
    detail,
    cashAccounts = [],
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    detail: MembershipDetail;
    cashAccounts?: CashAccount[];
}) {
    const { membership, totalPaid, balanceDue, isPaid } = detail;
    const defaultDate = () => new Date().toISOString().slice(0, 10);
    const paymentForm = useForm({
        advisor_membership_installment_id: '' as number | '',
        cash_account_id: '' as number | '',
        amount: '',
        paid_at: defaultDate(),
        notes: '',
    });

    const submitPayment = (e: FormEvent) => {
        e.preventDefault();
        const payload = {
            ...paymentForm.data,
            advisor_membership_installment_id: paymentForm.data.advisor_membership_installment_id === '' ? null : paymentForm.data.advisor_membership_installment_id,
            cash_account_id: paymentForm.data.cash_account_id === '' ? null : paymentForm.data.cash_account_id,
        };
        paymentForm.transform(() => payload).post(`/inmopro/advisor-memberships/${membership.id}/payments`, {
            onSuccess: () => paymentForm.reset('amount', 'paid_at', 'notes', 'advisor_membership_installment_id', 'cash_account_id'),
        });
    };

    const installments = membership.installments ?? [];
    const pendingInstallments = installments.filter((i) => i.status === 'PENDIENTE' || i.status === 'PARCIAL');

    const handleDestroy = async () => {
        if (await confirmDelete(`¿Eliminar la membresía de ${membership.advisor?.name ?? 'este vendedor'} (${membership.year})? Se eliminarán también todos los abonos.`)) {
            router.delete(`/inmopro/advisor-memberships/${membership.id}`);
            onOpenChange(false);
        }
    };

    const [editAmountOpen, setEditAmountOpen] = useState(false);
    const payments = membership.payments ?? [];
    const sortedPayments = [...payments].sort((a, b) => new Date(b.paid_at).getTime() - new Date(a.paid_at).getTime());

    const expired = membership.end_date ? new Date(membership.end_date) < new Date() : false;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Membresía {membership.year} – {membership.advisor?.name ?? 'Vendedor'}</DialogTitle>
                    <DialogDescription>
                        {membership.start_date && membership.end_date && (
                            <span className="block text-slate-600">
                                Inicio: {formatMembershipDate(membership.start_date)} · Vence: {formatMembershipDate(membership.end_date)}
                                {expired && <span className="ml-2 font-bold text-red-600">(Vencida)</span>}
                            </span>
                        )}
                        Monto S/ {Number(membership.amount).toLocaleString('es-PE')}
                        {isPaid ? ' · Al día' : ` · Pendiente S/ ${balanceDue.toLocaleString('es-PE')}`}
                    </DialogDescription>
                </DialogHeader>
                <div className="space-y-4">
                    {expired && (
                        <div className="rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm font-medium text-red-800">
                            Esta membresía está vencida. El vendedor puede seguir registrando abonos.
                        </div>
                    )}
                    <div className="grid grid-cols-3 gap-2 text-sm">
                        <div className="rounded-lg bg-slate-50 p-3">
                            <p className="text-slate-500">Monto</p>
                            <p className="font-bold">S/ {Number(membership.amount).toLocaleString('es-PE')}</p>
                        </div>
                        <div className="rounded-lg bg-slate-50 p-3">
                            <p className="text-slate-500">Abonado</p>
                            <p className="font-bold text-emerald-700">S/ {totalPaid.toLocaleString('es-PE')}</p>
                        </div>
                        <div className="rounded-lg bg-slate-50 p-3">
                            <p className="text-slate-500">Pendiente</p>
                            <p className={`font-bold ${balanceDue > 0 ? 'text-amber-600' : 'text-slate-500'}`}>S/ {balanceDue.toLocaleString('es-PE')}</p>
                        </div>
                    </div>

                    {installments.length > 0 && (
                        <div>
                            <h4 className="mb-2 text-sm font-semibold text-slate-700">Cuotas</h4>
                            <ul className="max-h-32 space-y-1 overflow-y-auto rounded border border-slate-100 p-2 text-sm">
                                {installments.map((i) => (
                                    <li key={i.id} className="flex justify-between gap-2">
                                        <span>Cuota {i.sequence} {i.due_date ? ` · Vence ${formatMembershipDate(i.due_date)}` : ''}</span>
                                        <span className="shrink-0">
                                            S/ {Number(i.paid_amount).toLocaleString('es-PE')} / S/ {Number(i.amount).toLocaleString('es-PE')}
                                            <span className={`ml-2 rounded px-1.5 py-0.5 text-[10px] font-bold ${
                                                i.status === 'PAGADA' ? 'bg-emerald-100 text-emerald-800' :
                                                i.status === 'VENCIDA' ? 'bg-red-100 text-red-800' :
                                                i.status === 'PARCIAL' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-600'
                                            }`}>
                                                {i.status}
                                            </span>
                                        </span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}

                    <div>
                        <h4 className="mb-2 text-sm font-semibold text-slate-700">Registrar abono</h4>
                        <form onSubmit={submitPayment} className="space-y-2">
                            <div className="flex flex-wrap items-end gap-2">
                                <div className="min-w-24">
                                    <Label className="text-xs">Monto (S/)</Label>
                                    <Input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        value={paymentForm.data.amount}
                                        onChange={(e) => paymentForm.setData('amount', e.target.value)}
                                        className="mt-1 h-9"
                                        required
                                    />
                                    <InputError message={paymentForm.errors.amount} />
                                </div>
                                <div className="min-w-32">
                                    <Label className="text-xs">Fecha</Label>
                                    <Input
                                        type="date"
                                        value={paymentForm.data.paid_at}
                                        onChange={(e) => paymentForm.setData('paid_at', e.target.value)}
                                        className="mt-1 h-9"
                                        required
                                    />
                                </div>
                                {pendingInstallments.length > 0 && (
                                    <div className="min-w-40">
                                        <Label className="text-xs">Cuota</Label>
                                        <select
                                            value={paymentForm.data.advisor_membership_installment_id === '' ? '' : String(paymentForm.data.advisor_membership_installment_id)}
                                            onChange={(e) => paymentForm.setData('advisor_membership_installment_id', e.target.value === '' ? '' : Number(e.target.value))}
                                            className="mt-1 h-9 w-full rounded-lg border border-slate-200 px-2 text-sm"
                                        >
                                            <option value="">A cuenta</option>
                                            {pendingInstallments.map((i) => (
                                                <option key={i.id} value={i.id}>Cuota {i.sequence} – S/ {Number(i.amount).toLocaleString('es-PE')}</option>
                                            ))}
                                        </select>
                                        <InputError message={paymentForm.errors.advisor_membership_installment_id} />
                                    </div>
                                )}
                                {cashAccounts.length > 0 && (
                                    <div className="min-w-40">
                                        <Label className="text-xs">Caja</Label>
                                        <select
                                            value={paymentForm.data.cash_account_id === '' ? '' : String(paymentForm.data.cash_account_id)}
                                            onChange={(e) => paymentForm.setData('cash_account_id', e.target.value === '' ? '' : Number(e.target.value))}
                                            className="mt-1 h-9 w-full rounded-lg border border-slate-200 px-2 text-sm"
                                        >
                                            <option value="">Sin caja</option>
                                            {cashAccounts.map((c) => (
                                                <option key={c.id} value={c.id}>{c.name}</option>
                                            ))}
                                        </select>
                                        <InputError message={paymentForm.errors.cash_account_id} />
                                    </div>
                                )}
                                <div className="min-w-28 flex-1">
                                    <Label className="text-xs">Observ.</Label>
                                    <Input
                                        value={paymentForm.data.notes}
                                        onChange={(e) => paymentForm.setData('notes', e.target.value)}
                                        className="mt-1 h-9"
                                    />
                                </div>
                                <Button type="submit" size="sm" disabled={paymentForm.processing}>Agregar</Button>
                            </div>
                        </form>
                    </div>

                    <div>
                        <h4 className="mb-2 text-sm font-semibold text-slate-700">Abonos ({sortedPayments.length})</h4>
                        {sortedPayments.length === 0 ? (
                            <p className="text-sm text-slate-500">Aún no hay abonos.</p>
                        ) : (
                            <ul className="max-h-40 space-y-1 overflow-y-auto rounded border border-slate-100 p-2 text-sm">
                                {sortedPayments.map((p) => (
                                    <li key={p.id} className="flex justify-between">
                                        <span>{new Date(p.paid_at).toLocaleDateString('es-PE')} {p.notes ? `· ${p.notes}` : ''}</span>
                                        <span className="font-medium">S/ {Number(p.amount).toLocaleString('es-PE')}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" onClick={() => setEditAmountOpen(true)}>Editar monto anual</Button>
                    <Button type="button" variant="outline" className="text-red-600" onClick={handleDestroy}>Eliminar membresía</Button>
                    <Button type="button" onClick={() => onOpenChange(false)}>Cerrar</Button>
                </DialogFooter>

                {editAmountOpen && (
                    <EditMembershipAmountModal
                        membership={membership}
                        open={editAmountOpen}
                        onOpenChange={setEditAmountOpen}
                    />
                )}
            </DialogContent>
        </Dialog>
    );
}

function EditMembershipAmountModal({
    membership,
    open,
    onOpenChange,
}: {
    membership: Membership;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, put, processing, errors } = useForm({
        amount: String(membership.amount),
    });

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(`/inmopro/advisor-memberships/${membership.id}`, { onSuccess: () => onOpenChange(false) });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Editar monto anual</DialogTitle>
                    <DialogDescription>Membresía {membership.year} – {membership.advisor?.name}</DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <Label htmlFor="edit-amount">Monto anual (S/)</Label>
                        <Input
                            id="edit-amount"
                            type="number"
                            step="0.01"
                            min="0"
                            value={data.amount}
                            onChange={(e) => setData('amount', e.target.value)}
                            className="mt-1"
                            required
                        />
                        <InputError message={errors.amount} />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>Cancelar</Button>
                        <Button type="submit" disabled={processing}>Guardar</Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
