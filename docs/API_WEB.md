# API Web (catálogo público)

## Resumen

API **sin autenticación** para sitios web o apps que necesitan mostrar el catálogo de **proyectos Inmopro** con medios (imágenes y vídeos), conteos de **lotes** y **lotes libres**.

- Base path: `/api/v1/web`
- Formato: JSON (`Content-Type: application/json`)
- Límite: `120` solicitudes por minuto por IP (`throttle:120,1`).
- Los archivos multimedia se sirven con una URL absoluta en cada activo; el mismo endpoint descarga o reproduce el binario según el `Content-Type`.

Relacionados:

- API vendedores (Cazador): [API_CAZADOR.md](./API_CAZADOR.md)
- API dateros: [API_DATERO.md](./API_DATERO.md)

## Resumen global (`summary`)

Presente en **`GET /projects`**:

| Campo | Descripción |
|--------|-------------|
| `projects_count` | Cantidad de proyectos |
| `lots_total` | Total de lotes en el sistema |
| `lots_free` | Lotes con estado `LIBRE` |
| `images_total` | Activos activos contados como imagen (`kind = image` o `mime_type` `image/*`) |
| `videos_total` | Activos activos contados como vídeo (`kind = video` o `mime_type` `video/*`) |

## Clasificación de medios por proyecto

- **Imágenes**: `kind === "image"` **o** `mime_type` empieza por `image/`.
- **Vídeos**: `kind === "video"` **o** `mime_type` empieza por `video/`.

Solo se incluyen activos con `is_active = true`.

Los **documentos** (PDF, etc.) no se exponen en este catálogo; si en el futuro deben listarse, se puede ampliar el controlador.

## Lotes libres

Un lote cuenta como **libre** si su estado (`lot_statuses.code`) es **`LIBRE`** (constante de negocio alineada con el panel Inmopro).

## Endpoints

### GET `/api/v1/web/projects`

Lista todos los proyectos ordenados por `name`, con el resumen global y el detalle por proyecto.

**Respuesta 200** (estructura):

```json
{
  "summary": {
    "projects_count": 3,
    "lots_total": 150,
    "lots_free": 42,
    "images_total": 12,
    "videos_total": 2
  },
  "data": [
    {
      "id": 1,
      "name": "Proyecto ejemplo",
      "location": "Lima",
      "blocks": [],
      "total_lots": 100,
      "lots_count": 100,
      "free_lots_count": 30,
      "project_type": {
        "id": 1,
        "name": "Lotes residenciales",
        "code": "RES"
      },
      "images_count": 4,
      "videos_count": 1,
      "images": [
        {
          "id": 10,
          "kind": "image",
          "title": "Masterplan",
          "file_name": "plan.png",
          "mime_type": "image/png",
          "file_size": 12345,
          "url": "https://tu-dominio.com/api/v1/web/projects/1/assets/10"
        }
      ],
      "videos": [
        {
          "id": 11,
          "kind": "video",
          "title": "Recorrido",
          "file_name": "tour.mp4",
          "mime_type": "video/mp4",
          "file_size": 900000,
          "url": "https://tu-dominio.com/api/v1/web/projects/1/assets/11"
        }
      ]
    }
  ]
}
```

### GET `/api/v1/web/projects/{id}`

Detalle de un proyecto; misma forma que un elemento de `data` en el listado (sin `summary`).

**Respuesta 200**:

```json
{
  "data": {
    "id": 1,
    "name": "Proyecto ejemplo",
    "location": "Lima",
    "blocks": [],
    "total_lots": 100,
    "lots_count": 100,
    "free_lots_count": 30,
    "project_type": { "id": 1, "name": "…", "code": "…" },
    "images_count": 4,
    "videos_count": 1,
    "images": [],
    "videos": []
  }
}
```

**404**: proyecto inexistente.

### GET `/api/v1/web/projects/{projectId}/assets/{assetId}`

Sirve el archivo almacenado en disco (`local`), con cabecera `Content-Type` del activo. Misma ruta que el campo `url` de cada imagen/vídeo.

- **404**: activo inactivo, otro proyecto, o archivo ausente en almacenamiento.

## Ejemplo con curl

```bash
curl -s "https://tu-dominio.com/api/v1/web/projects" | jq .
curl -s "https://tu-dominio.com/api/v1/web/projects/1" | jq .
```

## Notas de implementación

- Controlador: `App\Http\Controllers\Api\v1\Web\WebController`.
- Rutas nombradas: `api.v1.web.projects.index`, `api.v1.web.projects.show`, `api.v1.web.projects.assets.show`.
- Los archivos están en el disco `local` (`storage/app/private`), igual que en el panel Inmopro.
