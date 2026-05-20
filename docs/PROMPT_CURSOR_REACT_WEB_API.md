# Prompt maestro para Cursor: sitio web React + API Web (Laravel)

**Uso:** copia este archivo completo (o por secciones) en el chat de Cursor al crear o extender el **front web público** que consume el catálogo Inmopro. La fuente de verdad del contrato HTTP es [API_WEB.md](./API_WEB.md).

**Contexto típico:** backend **Laravel** (este repo, `crm_lotes`) expone JSON en `/api/v1/web`; el front es **React + TypeScript + Vite**, en el mismo monorepo (`resources/js`) o en un proyecto hermano que apunta al mismo `APP_URL`.

**Ajustes del equipo:** stack (React Router, TanStack Query, Tailwind) son **recomendados por defecto**. Sustitúyelos si el estándar interno es otro (p. ej. solo Inertia en rutas `/inmopro/*` — este prompt es para la **web pública**, no el panel administrativo).

---

## 1. Meta-instrucciones para el agente (Cursor)

Eres un desarrollador senior en **React**, **TypeScript** y consumo de **API REST** sobre **Laravel**. Debes:

- Implementar un sitio **público** (landing / catálogo de proyectos) que consume **únicamente** el API documentado en [API_WEB.md](./API_WEB.md). **No inventes** rutas, campos ni autenticación que no existan.
- **No** usar rutas ni sesión de Inmopro (`/inmopro/*`); ese panel es interno con login web.
- Priorizar **HTTPS** en producción.
- Textos de UI y comentarios en **español**.
- Usar las **props/tipos exactos** de la sección 6 (alineados al `WebController`).
- Para imágenes y vídeos usar **`asset.url`** del JSON (URL directa `/storage/...`); **no** reimplementar proxy salvo fallback documentado.
- Si falta un dato en el API, marca **TODO** y no llames endpoints no documentados (p. ej. no hay listado de lotes por proyecto en API Web).

---

## 2. Contexto del producto

- **Usuarios:** visitantes del sitio web (sin login).
- **Autenticación:** ninguna. No enviar `Authorization`.
- **URL base del API:**  
  `{{VITE_INMOPRO_API_URL}}/api/v1/web`  
  Ejemplo local con Herd: `https://crm-lotes.test/api/v1/web`.
- **Medios:** archivos en disco **`public`** del Laravel; el JSON trae `url` absoluta (`https://dominio/storage/projects/...`).
- **Flujo de pantallas sugerido:**
  1. **Inicio / catálogo** — `GET /projects` → tarjetas con nombre, ubicación, lotes libres, imagen de portada.
  2. **Detalle proyecto** — `GET /projects/{id}` → galería imágenes, reproductor o enlaces de vídeos, bloques, tipo de proyecto.
  3. (Opcional) **Estadísticas globales** en hero usando `summary` del listado.

No hay alta de clientes, pre-reservas ni login de vendedores en este API (ver [API_CAZADOR.md](./API_CAZADOR.md) para app móvil).

---

## 3. Configuración del proyecto (Laravel + React)

### 3.1 Mismo repositorio (recomendado)

El CRM ya incluye Vite + React en `resources/js`:

- Tipos: `resources/js/types/api-web.ts`
- Cliente HTTP: `resources/js/lib/api-web-client.ts`
- Import alias: `@/` → `resources/js/`

Crea páginas bajo `resources/js/pages/public/` o `resources/js/pages/catalog/` y regístralas en `routes/web.php` + entrada Vite si es SPA separada del bundle Inmopro.

### 3.2 Proyecto React separado

Si el sitio vive en otro repo:

1. Copia los tipos de la sección 6 o los archivos `api-web.ts` / `api-web-client.ts` del monorepo.
2. Define `VITE_INMOPRO_API_URL=https://crm-lotes.test` en `.env`.
3. Configura CORS en Laravel si el front corre en otro origen (dominio distinto).

### 3.3 Variables de entorno (Vite)

```env
# Origen del backend Laravel (sin barra final)
VITE_INMOPRO_API_URL=https://crm-lotes.test
```

Validar al arrancar que está definida en desarrollo; en producción debe ser HTTPS.

### 3.4 Cabeceras HTTP

- Siempre: `Accept: application/json`.
- **No** `Content-Type` en GET.
- **No** token Bearer.

### 3.5 Backend (operaciones una vez)

En el servidor Laravel:

```bash
php artisan storage:link
```

`APP_URL` debe coincidir con el dominio público (las `url` de imágenes dependen de ello).

---

## 4. Tabla maestra de endpoints

Prefijo: **`/api/v1/web`** (montado bajo `/api` en `routes/api.php`).

| Método | Ruta | Auth | Notas |
|--------|------|------|--------|
| GET | `projects` | No | Incluye `summary` + `meta` + `data[]`; paginación y filtros por query; throttle 120/min por IP |
| GET | `projects/{id}` | No | Un proyecto; forma igual a un ítem de `data` |
| GET | `projects/{project}/assets/{asset}` | No | **Opcional:** redirección 302 a `url` pública; preferir `url` del JSON |

---

## 5. Reglas de negocio obligatorias

### 5.1 Lotes libres

- `free_lots_count` cuenta lotes cuyo estado tiene código **`LIBRE`**.
- `lots_count` es el total de lotes del proyecto en BD.
- `summary.lots_free` es el total global de lotes libres.

### 5.2 Medios

- Solo activos con `is_active = true`.
- **Imágenes:** `kind === "image"` o `mime_type` empieza por `image/`.
- **Vídeos:** `kind === "video"` o `mime_type` empieza por `video/`.
- **Documentos PDF** no vienen en este API.

### 5.3 Uso de URLs

- Usar `images[n].url` y `videos[n].url` en `<img src>` / `<video src>` directamente.
- No transformar fechas en medios (no aplica).
- Si `url` devuelve 404, mostrar placeholder; el archivo pudo no migrarse a `storage/app/public`.

### 5.4 Rate limit

- Más de **120** peticiones/minuto por IP → **429**. Mostrar mensaje y reintentar más tarde.

---

## 6. Tipos TypeScript y props exactas (copiar o importar)

```typescript
// WEB_API_V1_BASE_PATH = '/api/v1/web'

export type WebProjectsIndexMeta = {
  current_page: number;
  per_page: number;
  total: number;
  last_page: number;
  from: number | null;
  to: number | null;
};

export type WebProjectsIndexQuery = {
  page?: number;
  per_page?: number;
  search?: string;
  location?: string;
  project_type_id?: number;
  has_free_lots?: boolean;
  has_images?: boolean;
  has_videos?: boolean;
  order?: 'name' | 'name_desc' | 'lots_desc' | 'free_lots_desc';
};

export type WebCatalogSummary = {
  projects_count: number;
  lots_total: number;
  lots_free: number;
  images_total: number;
  videos_total: number;
};

export type WebProjectType = {
  id: number;
  name: string;
  code: string;
};

export type WebProjectAsset = {
  id: number;
  kind: string;
  title: string;
  file_name: string;
  mime_type: string;
  file_size: number;
  url: string; // absoluta: .../storage/projects/{id}/...
};

export type WebProject = {
  id: number;
  name: string;
  location: string | null;
  blocks: string[];
  total_lots: number | null;
  lots_count: number;
  free_lots_count: number;
  project_type: WebProjectType | null;
  images: WebProjectAsset[];
  videos: WebProjectAsset[];
  images_count: number;
  videos_count: number;
};

// GET /api/v1/web/projects
export type WebProjectsIndexResponse = {
  summary: WebCatalogSummary;
  meta: WebProjectsIndexMeta;
  data: WebProject[];
};

// GET /api/v1/web/projects/{id}
export type WebProjectShowResponse = {
  data: WebProject;
};

/** Props del componente página catálogo */
export type WebProjectsCatalogPageProps = WebProjectsIndexResponse;

/** Props del componente página detalle */
export type WebProjectDetailPageProps = WebProjectShowResponse;
```

En este repo los archivos viven en `resources/js/types/api-web.ts` y el cliente en `resources/js/lib/api-web-client.ts`.

---

## 7. Cliente HTTP mínimo (referencia)

```typescript
import {
  fetchWebProjectsCatalog,
  fetchWebProject,
  resolveWebApiBaseUrl,
} from '@/lib/api-web-client';

const baseUrl = resolveWebApiBaseUrl();

// Listado (paginación y filtros opcionales)
const catalog: WebProjectsCatalogPageProps = await fetchWebProjectsCatalog(
  { baseUrl },
  {
    page: 1,
    per_page: 12,
    search: 'Olivos',
    has_free_lots: true,
    order: 'free_lots_desc',
  },
);

// Paginación en UI: catalog.meta.current_page, catalog.meta.last_page, catalog.meta.total

// Detalle
const detail: WebProjectDetailPageProps = await fetchWebProject(1, { baseUrl });
```

Envolver con **TanStack Query** (`queryKey: ['web', 'projects', query]`) para cache y estados loading/error; incluir los filtros en `queryKey` cuando cambien.

---

## 8. Modelo de errores en la UI

| Código | Acción en UI |
|--------|----------------|
| **404** | Proyecto no encontrado; enlace inválido |
| **429** | “Demasiadas solicitudes, intenta en un momento” |
| **5xx** | Mensaje genérico; log en desarrollo |
| Red caída | Mensaje de conectividad |
| JSON inválido | Error de datos; no crashear |

No hay **401** en este API.

---

## 9. Stack recomendado (Laravel + React)

| Área | Elección por defecto |
|------|----------------------|
| Backend | **Laravel 13** (ya desplegado) |
| Front | **React 19** + **TypeScript** + **Vite** |
| Estilos | **Tailwind CSS v4** (como el resto del monorepo) |
| Datos remotos | **TanStack Query** |
| HTTP | **fetch** tipado (`api-web-client.ts`) |
| Routing | **React Router** (SPA pública) o rutas Laravel que montan un root React |
| Imágenes | `<img loading="lazy" />`; vídeos `<video controls preload="metadata" />` |

**No usar Inertia** para este catálogo público salvo que el equipo decida una sola página Inertia que solo hace fetch al API Web (menos habitual).

---

## 10. Arquitectura de carpetas sugerida

```
resources/js/
  types/
    api-web.ts              # tipos y props (sección 6)
  lib/
    api-web-client.ts       # fetchWebProjectsCatalog, fetchWebProject
  pages/
    public/                 # o catalog/
      projects-index.tsx    # props: WebProjectsCatalogPageProps
      project-show.tsx      # props: WebProjectDetailPageProps
  components/
    catalog/
      ProjectCard.tsx
      ProjectGallery.tsx
      CatalogSummary.tsx
```

Si SPA separada del panel Inmopro, entrada dedicada en `vite.config.js` o `resources/js/public-main.tsx`.

---

## 11. Componentes y props (contrato UI)

### `CatalogSummary`

```typescript
type Props = { summary: WebCatalogSummary };
```

Muestra: proyectos, lotes libres, totales de imágenes/vídeos.

### `ProjectCard`

```typescript
type Props = { project: WebProject; href: string };
```

- Título: `project.name`
- Subtítulo: `project.location ?? '—'`
- Badge: `{project.free_lots_count} lotes libres`
- Imagen: `project.images[0]?.url` con fallback si no hay
- Tipo: `project.project_type?.name`

### `ProjectDetailPage`

```typescript
type Props = WebProjectDetailPageProps;
```

- Hero con primera imagen o carrusel `project.images`
- Lista `project.videos` con `<video src={url} />` o enlace externo
- Chips de `project.blocks`
- Contadores `lots_count` / `free_lots_count`

---

## 12. Ejemplo de página contenedor (React)

```tsx
import { useQuery } from '@tanstack/react-query';
import {
  fetchWebProjectsCatalog,
  resolveWebApiBaseUrl,
} from '@/lib/api-web-client';
import type { WebProjectsCatalogPageProps } from '@/types/api-web';
import { ProjectCard } from '@/components/catalog/ProjectCard';
import { CatalogSummary } from '@/components/catalog/CatalogSummary';

export function ProjectsCatalogPage(props: WebProjectsCatalogPageProps) {
  const { summary, meta, data } = props;

  return (
    <main className="mx-auto max-w-6xl p-6">
      <CatalogSummary summary={summary} />
      <ul className="mt-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
        {data.map((project) => (
          <li key={project.id}>
            <ProjectCard project={project} href={`/proyectos/${project.id}`} />
          </li>
        ))}
      </ul>
    </main>
  );
}

export function ProjectsCatalogContainer() {
  const { data, isLoading, isError, error } = useQuery({
    queryKey: ['web', 'projects'],
    queryFn: () => fetchWebProjectsCatalog({ baseUrl: resolveWebApiBaseUrl() }),
  });

  if (isLoading) return <p className="p-6">Cargando catálogo…</p>;
  if (isError) return <p className="p-6 text-red-600">{(error as Error).message}</p>;
  if (!data) return null;

  return <ProjectsCatalogPage {...data} />;
}
```

---

## 13. Checklist de aceptación (QA)

- [ ] `GET /projects` sin cabeceras de auth devuelve 200 con `summary`, `meta` y `data`.
- [ ] Paginación/filtros documentados en [API_WEB.md](./API_WEB.md) funcionan (`meta.total`, `page`, `search`).
- [ ] Cada proyecto muestra `free_lots_count` coherente con la UI.
- [ ] Imagen de portada carga desde `images[0].url` (ruta `/storage/...`).
- [ ] Detalle `GET /projects/{id}` muestra galería y vídeos.
- [ ] Proyecto inexistente → 404 manejado en UI.
- [ ] `VITE_INMOPRO_API_URL` apunta al Laravel correcto en dev y prod.
- [ ] En producción: `php artisan storage:link` y `APP_URL` HTTPS.
- [ ] No hay llamadas a `/api/v1/cazador` ni `/inmopro` en el sitio público.
- [ ] Lighthouse / accesibilidad: `alt` en imágenes usando `asset.title`.

---

## 14. Referencias en este repositorio

| Recurso | Ruta |
|---------|------|
| Contrato HTTP | [API_WEB.md](./API_WEB.md) |
| Controlador | `app/Http/Controllers/Api/v1/Web/WebController.php` |
| Rutas | `routes/api.php` → grupo `v1/web` |
| Tipos TS | `resources/js/types/api-web.ts` |
| Cliente fetch | `resources/js/lib/api-web-client.ts` |
| Tests API | `tests/Feature/Api/Web/WebCatalogTest.php` |
| API móvil vendedores | [API_CAZADOR.md](./API_CAZADOR.md) |

---

## 15. Anexo: cómo usar este archivo en Cursor

1. Abre el repo **crm_lotes** en Cursor (o el workspace que contiene Laravel + `resources/js`).
2. En el chat escribe: *Implementa el catálogo web público según el documento adjunto* y referencia **`@docs/PROMPT_CURSOR_REACT_WEB_API.md`** y **`@docs/API_WEB.md`**.
3. Orden sugerido al agente:
   - Copiar/verificar tipos en `api-web.ts` y cliente en `api-web-client.ts`.
   - Páginas `ProjectsCatalogPage` + `ProjectDetailPage` con props exactas.
   - Componentes `ProjectCard`, galería, manejo de errores.
   - Rutas React Router o wiring Laravel + Vite.
4. Valida con curl de [API_WEB.md](./API_WEB.md) antes de cerrar la tarea.
5. **No** mezclar con el prompt de React Native Cazador ([PROMPT_CURSOR_REACT_NATIVE_CAZADOR.md](./PROMPT_CURSOR_REACT_NATIVE_CAZADOR.md)) salvo enlaces “contactar asesor” que sean marketing, no API.

### Mensaje corto para pegar en Cursor

```
Implementa un catálogo web público en React + TypeScript + Vite (Tailwind) consumiendo solo GET /api/v1/web/projects y GET /api/v1/web/projects/{id}. Sin autenticación. Usa las props exactas WebProjectsCatalogPageProps y WebProjectDetailPageProps. Imágenes/vídeos con asset.url (/storage/...). Sigue @docs/PROMPT_CURSOR_REACT_WEB_API.md y @docs/API_WEB.md. Cliente: @resources/js/lib/api-web-client.ts. Tipos: @resources/js/types/api-web.ts.
```

---

*Documento para alinear el front web React con el API Web del CRM Lotes. Actualizar si cambia `API_WEB.md` o `WebController`.*
