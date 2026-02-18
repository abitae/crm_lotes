import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';

type Client = { id: number; name: string; dni: string; phone: string; email?: string; referred_by?: string };

export default function ClientsEdit({ client }: { client: Client }) {
    const { data, setData, put, processing, errors } = useForm({
        name: client.name,
        dni: client.dni,
        phone: client.phone,
        email: client.email ?? '',
        referred_by: client.referred_by ?? '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Clientes', href: '/inmopro/clients' },
        { title: 'Editar', href: `/inmopro/clients/${client.id}/edit` },
    ];

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(`/inmopro/clients/${client.id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${client.name} - Inmopro`} />
            <div className="p-4">
                <h2 className="mb-6 text-2xl font-black text-slate-800">Editar Cliente</h2>
                <form onSubmit={submit} className="max-w-md space-y-4">
                    <div>
                        <Label htmlFor="name">Nombre</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            className="mt-1"
                        />
                        <InputError message={errors.name} />
                    </div>
                    <div>
                        <Label htmlFor="dni">DNI</Label>
                        <Input
                            id="dni"
                            value={data.dni}
                            onChange={(e) => setData('dni', e.target.value)}
                            className="mt-1"
                        />
                        <InputError message={errors.dni} />
                    </div>
                    <div>
                        <Label htmlFor="phone">Teléfono</Label>
                        <Input
                            id="phone"
                            value={data.phone}
                            onChange={(e) => setData('phone', e.target.value)}
                            className="mt-1"
                        />
                        <InputError message={errors.phone} />
                    </div>
                    <div>
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            className="mt-1"
                        />
                        <InputError message={errors.email} />
                    </div>
                    <div>
                        <Label htmlFor="referred_by">Referido por</Label>
                        <Input
                            id="referred_by"
                            value={data.referred_by}
                            onChange={(e) => setData('referred_by', e.target.value)}
                            className="mt-1"
                        />
                    </div>
                    <Button type="submit" disabled={processing}>
                        Actualizar
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
