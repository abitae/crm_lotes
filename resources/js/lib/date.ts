export function todayIsoDate(): string {
    const today = new Date();
    today.setMinutes(today.getMinutes() - today.getTimezoneOffset());

    return today.toISOString().slice(0, 10);
}

export function nowIsoDateTimeLocal(): string {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());

    return now.toISOString().slice(0, 16);
}

export function toIsoDate(value?: string | null): string {
    if (!value) {
        return '';
    }

    const normalized = value.trim();

    if (/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
        return normalized;
    }

    const match = normalized.match(/^(\d{2})\/(\d{2})\/(\d{4})$/);

    if (match) {
        const [, day, month, year] = match;

        return `${year}-${month}-${day}`;
    }

    const parsed = new Date(normalized);

    if (Number.isNaN(parsed.getTime())) {
        return '';
    }

    parsed.setMinutes(parsed.getMinutes() - parsed.getTimezoneOffset());

    return parsed.toISOString().slice(0, 10);
}

export function formatDate(value?: string | null): string {
    const isoDate = toIsoDate(value);

    if (!isoDate) {
        return '-';
    }

    const [year, month, day] = isoDate.split('-');

    if (!year || !month || !day) {
        return '-';
    }

    return `${day}/${month}/${year}`;
}

export function formatDateTime(value?: string | null): string {
    if (!value) {
        return '-';
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return formatDate(value);
    }

    return parsed.toLocaleString('es-PE', {
        day: '2-digit',
        month: '2-digit',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
