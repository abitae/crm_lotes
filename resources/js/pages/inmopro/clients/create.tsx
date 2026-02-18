import { Head, useForm } from '@inertiajs/react';
import { FormEvent } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/input-error';
import type { BreadcrumbItem } from '@/types';

export default function ClientsCreate() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        dni: '',
        phone: '',
        email: '',
        referred_by: '',
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Clientes', href: '/inmopro/clients' },
        { title: 'Nuevo', href: '/inmopro/clients/create' },
    ];

    const submit = (e: FormEvent) => {
        e.preventDefault();
        post('/inmopro/clients');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo Cliente - Inmopro" />
            <div className="p-4 md:p-6">
                <div className="mb-6">
                    <h1 className="text-2xl font-bold tracking-tight text-slate-900">Nuevo cliente</h1>
                    <p className="mt-1 text-sm text-slate-500">Registre los datos del cliente.</p>
                </div>
                <Card className="max-w-lg">
                    <CardHeader>
                        <CardTitle>Datos del cliente</CardTitle>
                        <CardDescription>Complete los campos obligatorios.</CardDescription>
                    </CardHeader>
                    <CardContent>
                <form onSubmit={submit} className="space-y-4">
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
                    <div className="flex gap-2 pt-2">
                        <Button type="submit" disabled={processing}>
                            Guardar
                        </Button>
                        <Button type="button" variant="outline" onClick={() => window.history.back()}>
                            Cancelar
                        </Button>
                    </div>
                </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
