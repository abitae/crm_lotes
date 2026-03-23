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
        tagline: string | null;
        primary_color: string | null;
        logo_url: string | null;
        favicon_url: string | null;
    };
};

const DEFAULT_PRIMARY = '#059669';

export default function Branding({ branding }: BrandingProps) {
    const { data, setData, put, processing, errors } = useForm({
        display_name: branding.display_name ?? '',
        tagline: branding.tagline ?? '',
        primary_color: branding.primary_color ?? DEFAULT_PRIMARY,
        logo: null as File | null,
        favicon: null as File | null,
        remove_logo: false,
        remove_favicon: false,
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
                <h2 className="mb-2 text-2xl font-black text-slate-800 dark:text-slate-100">Personalización</h2>
                <p className="mb-6 max-w-2xl text-sm text-slate-600 dark:text-slate-400">
                    Nombre visible, eslogan, color de acento del menú, logotipo y favicon del navegador. Si dejas el nombre
                    vacío, se usará <code className="text-xs">APP_NAME</code> del servidor. El color por defecto es verde
                    esmeralda (#059669).
                </p>
                <form onSubmit={submit} className="max-w-lg space-y-6">
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
                        <Label htmlFor="tagline">Eslogan (opcional)</Label>
                        <Input
                            id="tagline"
                            type="text"
                            value={data.tagline}
                            onChange={(e) => setData('tagline', e.target.value)}
                            className="mt-1"
                            placeholder="Ej. Venta de lotes y viviendas"
                            maxLength={255}
                        />
                        <p className="mt-1 text-xs text-slate-500">Se muestra bajo el nombre en la barra lateral.</p>
                        <InputError message={errors.tagline} />
                    </div>

                    <div>
                        <Label htmlFor="primary_color">Color de acento</Label>
                        <div className="mt-1 flex flex-wrap items-center gap-3">
                            <Input
                                id="primary_color"
                                type="color"
                                value={data.primary_color?.startsWith('#') ? data.primary_color : DEFAULT_PRIMARY}
                                onChange={(e) => setData('primary_color', e.target.value)}
                                className="h-10 w-16 cursor-pointer p-1"
                            />
                            <Input
                                type="text"
                                value={data.primary_color}
                                onChange={(e) => setData('primary_color', e.target.value)}
                                placeholder="#059669"
                                className="max-w-[140px] font-mono text-sm"
                                maxLength={7}
                                spellCheck={false}
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={() => setData('primary_color', DEFAULT_PRIMARY)}
                            >
                                Restaurar predeterminado
                            </Button>
                        </div>
                        <p className="mt-1 text-xs text-slate-500">Formato hexadecimal (#RRGGBB). Deja el campo vacío al guardar para usar el predeterminado.</p>
                        <InputError message={errors.primary_color} />
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

                    <div>
                        <Label htmlFor="favicon">Favicon</Label>
                        <Input
                            id="favicon"
                            type="file"
                            accept="image/*"
                            className="mt-1 cursor-pointer"
                            onChange={(e) => setData('favicon', e.target.files?.[0] ?? null)}
                        />
                        <p className="mt-1 text-xs text-slate-500">Icono de la pestaña del navegador. Máx. 512 KB. Recomendado 32×32 o 64×64 px.</p>
                        <InputError message={errors.favicon} />
                    </div>

                    {branding.logo_url && (
                        <div className="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                            <p className="text-sm font-medium text-slate-700 dark:text-slate-300">Vista previa del logotipo</p>
                            <div className="flex h-16 w-16 items-center justify-center overflow-hidden rounded-md bg-white p-1 ring-1 ring-slate-200 dark:bg-slate-950 dark:ring-slate-700">
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

                    {branding.favicon_url && (
                        <div className="space-y-2 rounded-lg border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-900/40">
                            <p className="text-sm font-medium text-slate-700 dark:text-slate-300">Favicon actual</p>
                            <div className="flex h-10 w-10 items-center justify-center overflow-hidden rounded bg-white p-1 ring-1 ring-slate-200 dark:bg-slate-950 dark:ring-slate-700">
                                <img src={branding.favicon_url} alt="" className="max-h-full max-w-full object-contain" />
                            </div>
                            <div className="flex items-center gap-2">
                                <Checkbox
                                    id="remove_favicon"
                                    checked={data.remove_favicon}
                                    onCheckedChange={(checked) => setData('remove_favicon', checked === true)}
                                />
                                <Label htmlFor="remove_favicon" className="cursor-pointer font-normal">
                                    Quitar favicon personalizado
                                </Label>
                            </div>
                            <InputError message={errors.remove_favicon} />
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
