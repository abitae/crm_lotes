import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, Building2 } from 'lucide-react';
import type { PropsWithChildren } from 'react';
import { home } from '@/routes';

type SharedPageProps = {
    name: string;
    brandingPrimaryColor?: string;
};

type LegalDocumentLayoutProps = PropsWithChildren<{
    title: string;
    headTitle: string;
}>;

export default function LegalDocumentLayout({ title, headTitle, children }: LegalDocumentLayoutProps) {
    const { name, brandingPrimaryColor } = usePage<SharedPageProps>().props;
    const accent = brandingPrimaryColor ?? '#059669';

    return (
        <>
            <Head title={headTitle} />
            <div className="min-h-screen bg-slate-50 text-slate-900 dark:bg-slate-950 dark:text-slate-100">
                <header className="border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800 dark:bg-slate-900/90">
                    <div className="mx-auto flex max-w-3xl flex-wrap items-center justify-between gap-4 px-6 py-5">
                        <div className="flex items-center gap-3">
                            <span
                                className="flex h-10 w-10 items-center justify-center rounded-xl text-white shadow-md"
                                style={{ backgroundColor: accent }}
                            >
                                <Building2 className="h-5 w-5" aria-hidden />
                            </span>
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-widest text-emerald-700 dark:text-emerald-400">
                                    Documentación legal
                                </p>
                                <p className="text-base font-bold tracking-tight">{name}</p>
                            </div>
                        </div>
                        <Link
                            href={home()}
                            className="inline-flex items-center gap-2 text-sm font-medium text-slate-600 transition hover:text-slate-900 dark:text-slate-400 dark:hover:text-slate-100"
                        >
                            <ArrowLeft className="h-4 w-4" aria-hidden />
                            Volver al inicio
                        </Link>
                    </div>
                </header>
                <main className="mx-auto max-w-3xl px-6 py-10">
                    <h1 className="text-3xl font-bold tracking-tight text-slate-900 dark:text-white">{title}</h1>
                    <p className="mt-2 text-sm text-slate-600 dark:text-slate-400">
                        Última actualización: 21 de marzo de 2026
                    </p>
                    <div className="mt-10">{children}</div>
                </main>
            </div>
        </>
    );
}
