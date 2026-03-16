import { toIsoDate } from '@/lib/date';

export function toDateStr(value: string | undefined | null): string {
    return toIsoDate(value);
}

export function toNum(value: string | number | undefined | null): number | null {
    if (value == null || value === '') {
        return null;
    }

    const normalized = Number(value);

    return Number.isNaN(normalized) ? null : normalized;
}
