import { Head, Link, usePage } from '@inertiajs/react';
import { Building2 } from 'lucide-react';

type Shared = {
    name: string;
    brandingPrimaryColor?: string;
};

export default function DateroInviteInvalid() {
    const { name, brandingPrimaryColor } = usePage<Shared>().props;
    const accent = brandingPrimaryColor ?? '#059669';

    return (
        <div className="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
            <Head title="Enlace no disponible" />
            <header className="border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
                <div className="mx-auto flex max-w-lg items-center gap-3 px-6 py-5">
                    <span
                        className="flex h-10 w-10 items-center justify-center rounded-xl text-white shadow-md"
                        style={{ backgroundColor: accent }}
                    >
                        <Building2 className="h-5 w-5" aria-hidden />
                    </span>
                    <p className="text-base font-bold tracking-tight">{name}</p>
                </div>
            </header>
            <main className="mx-auto max-w-lg px-6 py-12 text-center">
                <h1 className="text-2xl font-bold text-slate-900 dark:text-white">Enlace no disponible</h1>
                <p className="mt-3 text-sm text-slate-600 dark:text-slate-400">
                    Este enlace de registro no es válido o ya no está activo. Solicita un código nuevo a quien te lo compartió.
                </p>
                <Link
                    href="/"
                    className="mt-8 inline-flex items-center justify-center rounded-xl px-4 py-2.5 text-sm font-semibold text-white shadow-md transition hover:opacity-95"
                    style={{ backgroundColor: accent }}
                >
                    Ir al inicio
                </Link>
            </main>
        </div>
    );
}
