import { Head, Link, router } from '@inertiajs/react';
import { Search, UserPlus, Eye, Mail, Phone, Users } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';

type Client = {
    id: number;
    name: string;
    dni: string;
    phone: string;
    email?: string;
    lots_count?: number;
};

export default function ClientsIndex({
    clients,
    filters,
}: {
    clients: { data: Client[]; links: unknown[] };
    filters: { search?: string };
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Clientes', href: '/inmopro/clients' },
    ];

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        const form = e.target as HTMLFormElement;
        const q = new FormData(form).get('search') as string;
        router.get('/inmopro/clients', { search: q || undefined }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Clientes - Inmopro" />
            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight text-slate-900">Clientes</h1>
                        <p className="mt-1 text-sm text-slate-500">Base de datos de compradores e interesados.</p>
                    </div>
                    <Button size="sm" asChild>
                        <Link href="/inmopro/clients/create">
                            <UserPlus className="h-4 w-4" />
                            Nuevo cliente
                        </Link>
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Buscar</CardTitle>
                        <CardDescription>Por nombre, DNI o teléfono.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSearch} className="flex gap-2">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input name="search" type="text" placeholder="Nombre, DNI o celular..." className="pl-9" defaultValue={filters.search} />
                            </div>
                            <Button type="submit" variant="secondary">Buscar</Button>
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
                                <p className="mt-1 text-sm text-slate-500">No hay clientes con los criterios de búsqueda.</p>
                                <Button className="mt-4" variant="outline" asChild>
                                    <Link href="/inmopro/clients/create">Nuevo cliente</Link>
                                </Button>
                            </div>
                        ) : (
                    <div className="overflow-x-auto">
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="border-b border-slate-100 bg-slate-50/80">
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">Nombre / DNI</th>
                                    <th className="px-4 py-3 text-left font-medium text-slate-600">Contacto</th>
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
                                                <div className="flex items-center gap-1.5 text-xs">
                                                    <Phone className="h-3.5 w-3.5 text-slate-400" />
                                                    {client.phone}
                                                </div>
                                                <div className="flex items-center gap-1.5 text-xs">
                                                    <Mail className="h-3.5 w-3.5 text-slate-400" />
                                                    {client.email ?? '—'}
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-right tabular-nums text-slate-600">{client.lots_count ?? 0}</td>
                                        <td className="px-4 py-3 text-right">
                                            <Button variant="ghost" size="icon" className="h-8 w-8" asChild>
                                                <Link href={`/inmopro/clients/${client.id}`} title="Ver">
                                                    <Eye className="h-4 w-4" />
                                                </Link>
                                            </Button>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
