import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';
import { update } from '@/actions/App/Http/Controllers/Inmopro/AppBrandingController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type BrandingProps = {
    branding: {
        display_name: string | null;
        logo_url: string | null;
    };
};

export default function Branding({ branding }: BrandingProps) {
    const { data, setData, put, processing, errors } = useForm({
        display_name: branding.display_name ?? '',
        logo: null as File | null,
        remove_logo: false,
    });

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Inmopro', href: '/inmopro/dashboard' },
        { title: 'Personalización', href: '/inmopro/branding' },
    ];

    const submit = (e: FormEvent) => {
        e.preventDefault();
        put(update.url(), {
            forceFormData: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Personalización - Inmopro" />
            <div className="p-4 md:p-6">
                <h2 className="mb-2 text-2xl font-black text-slate-800">Personalización</h2>
                <p className="mb-6 max-w-xl text-sm text-slate-600">
                    Define el nombre visible en la aplicación y, si lo deseas, un logotipo. Si dejas el nombre vacío, se usará el
                    nombre configurado en el entorno del servidor (<code className="text-xs">APP_NAME</code>).
                </p>
                <form onSubmit={submit} className="max-w-md space-y-6">
                    <div>
                        <Label htmlFor="display_name">Nombre de la aplicación</Label>
                        <Input
                            id="display_name"
                            type="text"
                            value={data.display_name}
                            onChange={(e) => setData('display_name', e.target.value)}
                            className="mt-1"
                            placeholder="Ej. Mi Inmobiliaria"
                            maxLength={255}
                        />
                        <InputError message={errors.display_name} />
                    </div>

                    <div>
                        <Label htmlFor="logo">Logotipo</Label>
                        <Input
                            id="logo"
                            type="file"
                            accept="image/*"
                            className="mt-1 cursor-pointer"
                            onChange={(e) => setData('logo', e.target.files?.[0] ?? null)}
                        />
                        <p className="mt-1 text-xs text-slate-500">PNG, JPG, WebP, SVG. Máx. 2 MB.</p>
                        <InputError message={errors.logo} />
                    </div>

                    {branding.logo_url && (
                        <div className="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-4">
                            <p className="text-sm font-medium text-slate-700">Vista previa actual</p>
                            <div className="flex h-16 w-16 items-center justify-center overflow-hidden rounded-md bg-white p-1 ring-1 ring-slate-200">
                                <img src={branding.logo_url} alt="" className="max-h-full max-w-full object-contain" />
                            </div>
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="remove_logo"
                                    checked={data.remove_logo}
                                    onCheckedChange={(checked) => setData('remove_logo', checked === true)}
                                />
                                <Label htmlFor="remove_logo" className="cursor-pointer font-normal">
                                    Quitar logotipo personalizado
                                </Label>
                            </div>
                            <InputError message={errors.remove_logo} />
                        </div>
                    )}

                    <Button type="submit" disabled={processing}>
                        Guardar
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
