/// <reference types="vite/client" />

interface ImportMetaEnv {
    /** Origen del backend Laravel para GET /api/v1/web (ej. https://crm-lotes.test). */
    readonly VITE_INMOPRO_API_URL?: string;
}

interface ImportMeta {
    readonly env: ImportMetaEnv;
}
