export function toDateStr(value: string | undefined | null): string {
    if (value == null || value === '') {
        return '';
    }

    const normalized = String(value);

    if (normalized.includes('T')) {
        return normalized.slice(0, 10);
    }

    return normalized.slice(0, 10);
}

export function toNum(value: string | number | undefined | null): number | null {
    if (value == null || value === '') {
        return null;
    }

    const normalized = Number(value);

    return Number.isNaN(normalized) ? null : normalized;
}
