import { Link } from '@inertiajs/react';
import { ChevronLeft, ChevronRight } from 'lucide-react';

export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginationProps = {
    links: PaginationLink[];
    className?: string;
};

/**
 * Muestra los enlaces de paginación de Laravel (array links del paginator).
 * Oculta el contenedor si solo hay una página (prev, 1, next sin otras opciones).
 */
export default function Pagination({ links, className = '' }: PaginationProps) {
    const hasPages = links.some((l) => l.url !== null);
    if (!hasPages) return null;

    return (
        <nav
            role="navigation"
            aria-label="Paginación"
            className={`flex flex-wrap items-center justify-center gap-1 ${className}`}
        >
            {links.map((link, i) => {
                if (link.url === null) {
                    return (
                        <span
                            key={i}
                            className={`inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm font-medium ${
                                link.active
                                    ? 'bg-slate-900 text-white'
                                    : 'cursor-default text-slate-400'
                            }`}
                        >
                            {link.label === '&laquo; Previous' ? (
                                <ChevronLeft className="h-4 w-4" />
                            ) : link.label === 'Next &raquo;' ? (
                                <ChevronRight className="h-4 w-4" />
                            ) : (
                                link.label
                            )}
                        </span>
                    );
                }
                return (
                    <Link
                        key={i}
                        href={link.url}
                        preserveState
                        className={`inline-flex h-9 min-w-9 items-center justify-center rounded-md px-3 text-sm font-medium transition-colors ${
                            link.active
                                ? 'bg-slate-900 text-white'
                                : 'bg-white text-slate-700 hover:bg-slate-100 border border-slate-200'
                        }`}
                    >
                        {link.label === '&laquo; Previous' ? (
                            <ChevronLeft className="h-4 w-4" />
                        ) : link.label === 'Next &raquo;' ? (
                            <ChevronRight className="h-4 w-4" />
                        ) : (
                            link.label
                        )}
                    </Link>
                );
            })}
        </nav>
    );
}
