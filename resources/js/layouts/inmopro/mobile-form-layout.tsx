import { Link } from '@inertiajs/react';
import { ArrowLeft } from 'lucide-react';
import type { ReactNode } from 'react';
import FlashSwal from '@/components/flash-swal';
import { cn } from '@/lib/utils';

type InmoproMobileFormLayoutProps = {
    title: string;
    description?: string;
    backHref: string;
    backLabel?: string;
    children: ReactNode;
    className?: string;
};

export default function InmoproMobileFormLayout({
    title,
    description,
    backHref,
    backLabel = 'Volver',
    children,
    className,
}: InmoproMobileFormLayoutProps) {
    return (
        <div className="flex min-h-svh flex-col bg-slate-50 text-slate-900">
            <FlashSwal />
            <header className="sticky top-0 z-20 border-b border-slate-200/80 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
                <div className="mx-auto flex w-full max-w-xl items-start gap-3 px-4 py-3">
                    <Link
                        href={backHref}
                        className="mt-0.5 inline-flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow-sm transition-colors hover:bg-slate-50"
                        aria-label={backLabel}
                    >
                        <ArrowLeft className="h-5 w-5" aria-hidden />
                    </Link>
                    <div className="min-w-0 flex-1 pt-0.5">
                        <p className="text-[11px] font-semibold uppercase tracking-[0.18em] text-emerald-700">Inmopro</p>
                        <h1 className="truncate text-lg font-semibold tracking-tight text-slate-900">{title}</h1>
                        {description ? <p className="mt-1 text-sm leading-snug text-slate-500">{description}</p> : null}
                    </div>
                </div>
            </header>
            <main className={cn('mx-auto w-full max-w-xl flex-1 px-4 py-4 pb-[max(1rem,env(safe-area-inset-bottom))]', className)}>
                {children}
            </main>
        </div>
    );
}
