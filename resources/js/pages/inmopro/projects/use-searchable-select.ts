import { useCallback, useRef, useState } from 'react';

export const SEARCH_DEBOUNCE_MS = 250;
export const SEARCH_MIN_CHARS = 2;
export const SEARCH_CACHE_MAX = 50;

export async function searchClients(q: string) {
    const term = q.trim();

    if (!term) {
        return [];
    }

    const params = new URLSearchParams({ q: term });
    const response = await fetch(`/inmopro/clients/search?${params.toString()}`, { headers: { Accept: 'application/json' } });

    if (!response.ok) {
        return [];
    }

    return response.json();
}

export async function searchAdvisors(q: string) {
    const term = q.trim();

    if (!term) {
        return [];
    }

    const params = new URLSearchParams({ q: term });
    const response = await fetch(`/inmopro/advisors/search?${params.toString()}`, { headers: { Accept: 'application/json' } });

    if (!response.ok) {
        return [];
    }

    return response.json();
}

export function useSearchableSelect<T extends { id: number }>(
    fetchFn: (q: string) => Promise<T[]>,
    options: { debounceMs: number; minChars: number; cacheMax?: number }
) {
    const { debounceMs, minChars, cacheMax = 0 } = options;
    const [openKey, setOpenKey] = useState<string | null>(null);
    const [results, setResults] = useState<T[]>([]);
    const [loading, setLoading] = useState(false);
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const cacheRef = useRef<Map<string, T[]>>(new Map());

    const triggerSearch = useCallback(
        (q: string, key: string) => {
            if (debounceRef.current) {
                clearTimeout(debounceRef.current);
            }

            const term = q.trim();
            setOpenKey(key);

            if (term.length < minChars) {
                setResults([]);
                return;
            }

            if (cacheMax > 0) {
                const cacheKey = term.toLowerCase();
                const hit = cacheRef.current.get(cacheKey);

                if (hit !== undefined) {
                    setResults(hit);
                    return;
                }
            }

            debounceRef.current = setTimeout(() => {
                setLoading(true);

                fetchFn(term)
                    .then((data) => {
                        setResults(data);

                        if (cacheMax > 0 && data.length > 0) {
                            const cache = cacheRef.current;

                            if (cache.size >= cacheMax) {
                                const firstKey = cache.keys().next().value;

                                if (firstKey !== undefined) {
                                    cache.delete(firstKey);
                                }
                            }

                            cache.set(term.toLowerCase(), data);
                        }
                    })
                    .finally(() => setLoading(false));
            }, debounceMs);
        },
        [fetchFn, debounceMs, minChars, cacheMax]
    );

    return { openKey, setOpenKey, results, loading, triggerSearch };
}
