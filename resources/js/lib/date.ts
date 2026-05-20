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

export function parseCalendarDate(value?: string | null): Date | null {
    const isoDate = toIsoDate(value);

    if (!isoDate) {
        return null;
    }

    const [year, month, day] = isoDate.split('-').map((part) => parseInt(part, 10));

    if (!year || !month || !day) {
        return null;
    }

    return new Date(year, month - 1, day);
}

export function calendarDateTimestamp(value?: string | null): number {
    return parseCalendarDate(value)?.getTime() ?? 0;
}

export function calendarDateToIso(date: Date): string {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
}

export function addMonthsToIsoDate(value: string, months: number): string {
    const start = parseCalendarDate(value);

    if (!start) {
        return '';
    }

    const end = new Date(start);
    end.setMonth(end.getMonth() + months);

    return calendarDateToIso(end);
}

export function isDateOnlyValue(value: string): boolean {
    const normalized = value.trim();

    if (/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
        return true;
    }

    return /^\d{4}-\d{2}-\d{2}T00:00:00(\.000000Z?)?$/.test(normalized);
}

export function formatCalendarDate(
    value?: string | null,
    options: Intl.DateTimeFormatOptions = {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
    },
    locale = 'es-PE',
    empty = '—',
): string {
    const parsed = parseCalendarDate(value);

    if (!parsed) {
        return empty;
    }

    return parsed.toLocaleDateString(locale, options);
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

    const normalized = value.trim();

    if (isDateOnlyValue(normalized)) {
        return formatDate(normalized);
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
