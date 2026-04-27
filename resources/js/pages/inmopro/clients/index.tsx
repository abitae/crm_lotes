import { Head, Link, router, usePage } from '@inertiajs/react';
import { Download, Eye, FileSpreadsheet, Mail, Phone, Search, UserPlus, Users } from 'lucide-react';
import { useRef, useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import Pagination, { type PaginationLink } from '@/components/pagination';
import { clientsListingQuerySuffix } from '@/lib/inmopro-listing-query';
import type { BreadcrumbItem } from '@/types';

type Client = {
    id: number;
    name: string;
    dni: string;
    phone: string;
    email?: string;
    lots_count?: number;
    type?: { name: string; color?: string };
    city?: { name: string; department?: string | null };
    advisor?: { name: string; team?: { name: string } | null };
};
type Option = {
    id: number;
    name: string;
};

export default function ClientsIndex({
    clients,
    filters,
    clientTypes,
    cities,
    advisors,
}: {
    clients: { data: Client[]; links: PaginationLink[]; total?: number };
    filters: { search?: string; client_type_id?: string | number; city_id?: string | number; advisor_id?: string | number };
    clientTypes: Option[];
    cities: Option[];
    advisors: Option[];
}) {
    const totalClients = clients.total ?? clients.data.length;
    const clientsWithLots = clients.data.filter((client) => (client.lots_count ?? 0) > 0).length;
    const clientsWithEmail = clients.data.filter((client) => Boolean(client.email)).length;
    const fileInputRef = useRef<HTMLInputElement>(null);
    const [importing, setImporting] = useState(false);
    const listQs = clientsListingQuerySuffix(usePage().url);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Clientes', href: '/inmopro/clients' },
    ];

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        const form = e.currentTarget as HTMLFormElement;
        const q = new FormData(form).get('search') as string;
        const formData = new FormData(form);

        router.get('/inmopro/clients', {
            search: q || undefined,
            client_type_id: (formData.get('client_type_id') as string) || undefined,
            city_id: (formData.get('city_id') as string) || undefined,
            advisor_id: (formData.get('advisor_id') as string) || undefined,
        }, { preserveState: true });
    };

    const handleImportSubmit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        const input = fileInputRef.current;

        if (!input?.files?.length) {
            return;
        }

        setImporting(true);
        const formData = new FormData();
        formData.append('file', input.files[0]);

        router.post(`/inmopro/clients/import-from-excel${listQs}`, formData, {
            forceFormData: true,
            onFinish: () => {
                setImporting(false);
                input.value = '';
            },
        });
    };

    const exportQuery = new URLSearchParams();

    if (filters.search) {
        exportQuery.set('search', String(filters.search));
    }
    if (filters.client_type_id) {
        exportQuery.set('client_type_id', String(filters.client_type_id));
    }
    if (filters.city_id) {
        exportQuery.set('city_id', String(filters.city_id));
    }
    if (filters.advisor_id) {
        exportQuery.set('advisor_id', String(filters.advisor_id));
    }

    const exportHref = `/inmopro/clients/export-excel${exportQuery.toString() ? `?${exportQuery.toString()}` : ''}`;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Clientes - Inmopro" />
            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">Clientes</h1>
                        <p className="mt-1 text-sm text-slate-500">Base de datos de compradores e interesados.</p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <a href={exportHref}>
                                <Download className="h-4 w-4" />
                                Exportar Excel
                            </a>
                        </Button>
                        <form onSubmit={handleImportSubmit}>
                            <input
                                ref={fileInputRef}
                                type="file"
                                accept=".xlsx,.xls"
                                className="hidden"
                                onChange={(e) => {
                                    if (e.target.files?.length) {
                                        (e.target.form as HTMLFormElement).requestSubmit();
                                    }
                                }}
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                className="border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100"
                                onClick={() => fileInputRef.current?.click()}
                                disabled={importing}
                            >
                                <FileSpreadsheet className="h-4 w-4" />
                                {importing ? 'Importando...' : 'Importar Excel'}
                            </Button>
                        </form>
                        <Button size="sm" asChild>
                            <Link href={`/inmopro/clients/create${listQs}`}>
                                <UserPlus className="h-4 w-4" />
                                Nuevo cliente
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                    <SummaryCard label="Clientes totales" value={String(totalClients)} />
                    <SummaryCard label="Con lotes asociados" value={String(clientsWithLots)} tone="emerald" />
                    <SummaryCard label="Con email registrado" value={String(clientsWithEmail)} tone="blue" />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Filtros</CardTitle>
                        <CardDescription>Busque por nombre, DNI o telefono y filtre por tipo, ciudad o asesor.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="grid gap-3 lg:grid-cols-5">
                            <div className="relative lg:col-span-2">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input name="search" type="text" placeholder="Nombre, DNI o celular..." className="pl-9" defaultValue={filters.search} />
                            </div>
                            <select
                                name="client_type_id"
                                defaultValue={filters.client_type_id ? String(filters.client_type_id) : ''}
                                className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
                            >
                                <option value="">Todos los tipos</option>
                                {clientTypes.map((clientType) => (
                                    <option key={clientType.id} value={clientType.id}>{clientType.name}</option>
                                ))}
                            </select>
                            <select
                                name="city_id"
                                defaultValue={filters.city_id ? String(filters.city_id) : ''}
                                className="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
                            >
                                <option value="">Todas las ciudades</option>
                                {cities.map((city) => (
                                    <option key={city.id} value={city.id}>{city.name}</option>
                                ))}
                            </select>
                            <div className="flex gap-2">
                                <select
                                    name="advisor_id"
                                    defaultValue={filters.advisor_id ? String(filters.advisor_id) : ''}
                                    className="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm"
                                >
                                    <option value="">Todos los asesores</option>
                                    {advisors.map((advisor) => (
                                        <option key={advisor.id} value={advisor.id}>{advisor.name}</option>
                                    ))}
                                </select>
                                <Button type="submit" variant="secondary">Buscar</Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle>Directorio</CardTitle>
                        <CardDescription>{clients.data.length} cliente(s) encontrado(s).</CardDescription>
                    </CardHeader>
                    <CardContent className="p-0">
                        {clients.data.length === 0 ? (
                            <div className="flex flex-col items-center justify-center py-16 text-center">
                                <div className="rounded-full bg-slate-100 p-4">
                                    <Users className="h-10 w-10 text-slate-400" />
                                </div>
                                <p className="mt-4 font-medium text-slate-700">Sin coincidencias</p>
                                <p className="mt-1 text-sm text-slate-500">No hay clientes con los criterios de busqueda.</p>
                                <Button className="mt-4" variant="outline" asChild>
                                    <Link href={`/inmopro/clients/create${listQs}`}>Nuevo cliente</Link>
                                </Button>
                            </div>
                        ) : (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b border-slate-100 bg-slate-50/80">
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Nombre / DNI</th>
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Tipo / Contacto</th>
                                                <th className="px-4 py-3 text-left font-medium text-slate-600">Procedencia / Vendedor</th>
                                                <th className="px-4 py-3 text-right font-medium text-slate-600">Lotes</th>
                                                <th className="px-4 py-3 text-right font-medium text-slate-600">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-slate-50">
                                            {clients.data.map((client) => (
                                                <tr key={client.id} className="hover:bg-slate-50/50">
                                                    <td className="px-4 py-3">
                                                        <div className="flex items-center gap-3">
                                                            <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-xs font-semibold text-slate-600">
                                                                {client.name.split(' ').map((n) => n[0]).slice(0, 2).join('')}
                                                            </div>
                                                            <div>
                                                                <p className="font-medium text-slate-900">{client.name}</p>
                                                                <p className="text-xs text-slate-500">DNI: {client.dni}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="space-y-1 text-slate-600">
                                                            <div>
                                                                <span
                                                                    className="inline-flex rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-wider text-white"
                                                                    style={{ backgroundColor: client.type?.color ?? '#475569' }}
                                                                >
                                                                    {client.type?.name ?? 'Sin tipo'}
                                                                </span>
                                                            </div>
                                                            <div className="flex items-center gap-1.5 text-xs">
                                                                <Phone className="h-3.5 w-3.5 text-slate-400" />
                                                                {client.phone}
                                                            </div>
                                                            <div className="flex items-center gap-1.5 text-xs">
                                                                <Mail className="h-3.5 w-3.5 text-slate-400" />
                                                                {client.email ?? '-'}
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3">
                                                        <div className="space-y-1 text-xs text-slate-600">
                                                            <p>{client.city?.name ?? 'Sin ciudad'}{client.city?.department ? ` · ${client.city.department}` : ''}</p>
                                                            <p className="font-medium text-slate-700">{client.advisor?.name ?? 'Sin vendedor asignado'}</p>
                                                            <p className="text-slate-400">{client.advisor?.team?.name ?? '-'}</p>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3 text-right tabular-nums text-slate-600">{client.lots_count ?? 0}</td>
                                                    <td className="px-4 py-3 text-right">
                                                        <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                                                            <Link href={`/inmopro/clients/${client.id}${listQs}`} title="Ver">
                                                                <Eye className="h-4 w-4" />
                                                            </Link>
                                                        </Button>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                                <div className="border-t border-slate-100 px-4 py-3">
                                    <Pagination links={clients.links} />
                                </div>
                            </>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}

function SummaryCard({
    label,
    value,
    tone = 'slate',
}: {
    label: string;
    value: string;
    tone?: 'slate' | 'emerald' | 'blue';
}) {
    const tones = {
        slate: 'text-slate-900',
        emerald: 'text-emerald-600',
        blue: 'text-blue-600',
    };

    return (
        <div className="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
            <p className="text-[10px] font-black uppercase tracking-widest text-slate-400">{label}</p>
            <p className={`mt-3 text-3xl font-black ${tones[tone]}`}>{value}</p>
        </div>
    );
}
