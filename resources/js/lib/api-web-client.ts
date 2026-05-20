import {
    WEB_API_V1_BASE_PATH,
    type WebProjectShowResponse,
    type WebProjectsIndexResponse,
} from '@/types/api-web';

export type WebApiClientOptions = {
    /** Origen del backend, p. ej. `https://crm-lotes.test` (sin barra final). */
    baseUrl: string;
    headers?: HeadersInit;
    signal?: AbortSignal;
};

function buildUrl(baseUrl: string, path: string): string {
    const origin = baseUrl.replace(/\/$/, '');

    return `${origin}${path}`;
}

async function parseJson<T>(response: Response): Promise<T> {
    if (!response.ok) {
        let detail = response.statusText;

        try {
            const body = (await response.json()) as { message?: string };
            if (body.message) {
                detail = body.message;
            }
        } catch {
            // respuesta no JSON
        }

        throw new Error(`API Web ${response.status}: ${detail}`);
    }

    return response.json() as Promise<T>;
}

/** GET /api/v1/web/projects */
export async function fetchWebProjectsCatalog(options: WebApiClientOptions): Promise<WebProjectsIndexResponse> {
    const response = await fetch(buildUrl(options.baseUrl, `${WEB_API_V1_BASE_PATH}/projects`), {
        method: 'GET',
        headers: {
            Accept: 'application/json',
            ...options.headers,
        },
        signal: options.signal,
    });

    return parseJson<WebProjectsIndexResponse>(response);
}

/** GET /api/v1/web/projects/{id} */
export async function fetchWebProject(
    projectId: number,
    options: WebApiClientOptions,
): Promise<WebProjectShowResponse> {
    const response = await fetch(buildUrl(options.baseUrl, `${WEB_API_V1_BASE_PATH}/projects/${projectId}`), {
        method: 'GET',
        headers: {
            Accept: 'application/json',
            ...options.headers,
        },
        signal: options.signal,
    });

    return parseJson<WebProjectShowResponse>(response);
}

/** Origen del API: VITE_INMOPRO_API_URL o window.location.origin */
export function resolveWebApiBaseUrl(): string {
    const fromEnv = import.meta.env.VITE_INMOPRO_API_URL;

    if (typeof fromEnv === 'string' && fromEnv.trim() !== '') {
        return fromEnv.trim().replace(/\/$/, '');
    }

    if (typeof window !== 'undefined') {
        return window.location.origin;
    }

    return '';
}
