# API pública del sitio web

Documentación de la API que consume el sitio web del cliente para mostrar la información que se administra desde el panel.

- [Información general](#información-general)
- [Resumen de endpoints](#resumen-de-endpoints)
- [Empresa](#1-empresa) · [Banner](#2-banner) · [Pop-up](#3-pop-up) · [Proyectos](#4-proyectos) · [Categorías de proyectos](#5-categorías-de-proyectos) · [Testimonios](#6-testimonios) · [Portadas](#7-portadas)
- [Errores](#errores)
- [Ejemplos de uso](#ejemplos-de-uso)

---

## Información general

| | |
|---|---|
| **URL base** | `https://admin.tuempresa.com/api/v1` (en local: `http://localhost:8000/api/v1`) |
| **Formato** | JSON (UTF-8). Conviene enviar el encabezado `Accept: application/json`. |
| **Autenticación** | Ninguna. Es una API pública y de **solo lectura**. |
| **Métodos** | Solo `GET`. Cualquier otro método responde `405`. |
| **Límite de uso** | 60 peticiones por minuto por IP. Al superarlo responde `429` con el encabezado `Retry-After` (segundos de espera). Todas las respuestas traen `X-RateLimit-Limit` y `X-RateLimit-Remaining`. |
| **Caché** | Todas las respuestas correctas traen `Cache-Control: public, max-age=60`. Un cambio hecho en el panel puede tardar hasta 1 minuto en verse. |
| **CORS** | Solo pueden consumirla desde el navegador los dominios configurados en `CORS_ALLOWED_ORIGINS` del `.env` del panel. |

### Convenciones

- **Solo se publica lo que está visible.** Proyectos y testimonios ocultos, y un pop-up desactivado, no aparecen en la API.
- **Las URLs de imágenes y videos son absolutas**, ya listas para usar en `src`. Salen con el `APP_URL` del panel, por lo que en producción debe ser el dominio real.
- **Los campos vacíos llegan como `null`**, nunca se omiten. Así la estructura de cada respuesta es siempre la misma.
- **Contenido con formato (HTML).** `description` y `content` de los proyectos son HTML ya limpiado en el servidor: solo trae `p`, `div`, `br`, `strong`, `b`, `em`, `i`, `u`, `del`, `s`, `ul`, `ol`, `li`, `a`, `span`, `table`, `thead`, `tbody`, `tr`, `th` y `td`. Los estilos permitidos son solo color de texto, color de fondo (en `span`) y `text-align` (en `p`, `th` y `td`). Puede insertarse directamente en la página, **pero no trae estilos para las tablas**: el sitio debe darles CSS (`table`, `th`, `td`).
- **Texto plano.** `content` de los testimonios y los textos de los banners son texto plano (los testimonios pueden traer saltos de línea `\n`). Muéstralos escapados, por ejemplo con `textContent`, o con `white-space: pre-line` para respetar los saltos.
- **Fechas** en formato ISO 8601 (`2026-10-07T15:14:52+00:00`).

---

## Resumen de endpoints

| Método | Endpoint | Descripción |
|---|---|---|
| GET | `/company` | Datos de la empresa (contacto, redes, SEO, logo) |
| GET | `/banners` | Banner de la portada (slider de imágenes o un video) |
| GET | `/popup` | Ventana emergente, o `null` si no hay ninguna que mostrar |
| GET | `/projects` | Listado paginado de proyectos visibles (admite filtro por categoría) |
| GET | `/projects/{slug}` | Detalle de un proyecto |
| GET | `/project-categories` | Categorías que tienen proyectos visibles |
| GET | `/testimonials` | Testimonios visibles |
| GET | `/covers` | Portadas activas de las páginas internas |
| GET | `/covers/{page}` | Portada de una página interna, o `null` si no hay ninguna que mostrar |

---

## 1. Empresa

`GET /company`

Datos de "Datos del cliente". Sirve para el encabezado, el pie de página, el contacto, las redes sociales y las etiquetas SEO.

### Respuesta `200`

```json
{
  "data": {
    "name": "Voladizo Arquitectos",
    "legal_name": "Voladizo Arquitectos S.A.C.",
    "ruc": "20512345678",
    "slogan": "Diseñamos espacios que inspiran",
    "description": "Estudio de arquitectura especializado en proyectos residenciales y comerciales.",
    "logo_url": "https://admin.tuempresa.com/storage/files/2026/10/logo.png",
    "favicon_url": null,
    "contact": {
      "address": "Av. Javier Prado 1234, San Isidro, Lima",
      "maps_url": "https://maps.google.com/?q=-12.09,-77.03",
      "business_hours": "Lunes a viernes de 9:00 a 18:00",
      "email": "contacto@voladizo.pe",
      "email_2": null,
      "phone": "+51 1 555 0100",
      "whatsapp": "+51 999 888 777",
      "whatsapp_2": null
    },
    "social": {
      "website": "https://voladizo.pe",
      "facebook": "https://facebook.com/voladizo",
      "instagram": "https://instagram.com/voladizo",
      "youtube": null,
      "tiktok": null,
      "linkedin": null,
      "behance": null
    },
    "seo": {
      "title": "Voladizo Arquitectos | Arquitectura en Lima",
      "description": "Proyectos residenciales, oficinas y comerciales en Lima."
    },
    "updated_at": "2026-10-07T15:14:52+00:00"
  }
}
```

| Campo | Tipo | Descripción |
|---|---|---|
| `name` | string | Nombre comercial |
| `legal_name` | string \| null | Razón social |
| `ruc` | string \| null | RUC |
| `slogan` | string \| null | Eslogan |
| `description` | string \| null | Descripción de la empresa |
| `logo_url` | string \| null | URL absoluta del logo |
| `favicon_url` | string \| null | URL absoluta del favicon |
| `contact.address` | string \| null | Dirección |
| `contact.maps_url` | string \| null | Enlace a Google Maps |
| `contact.business_hours` | string \| null | Horario de atención |
| `contact.email`, `contact.email_2` | string \| null | Correos |
| `contact.phone` | string \| null | Teléfono |
| `contact.whatsapp`, `contact.whatsapp_2` | string \| null | Números de WhatsApp |
| `social.website` | string \| null | Sitio web |
| `social.facebook`, `instagram`, `youtube`, `tiktok`, `linkedin`, `behance` | string \| null | URLs de las redes sociales |
| `seo.title` | string \| null | Título para `<title>` / `og:title` |
| `seo.description` | string \| null | Descripción para `meta description` |
| `updated_at` | string | Última modificación |

Los campos opcionales que no se llenaron en el panel llegan como `null`; la estructura es siempre completa.

### Respuesta `404`

Aún no se han guardado los datos de la empresa en el panel.

```json
{
  "message": "Los datos de la empresa aún no han sido configurados."
}
```

---

## 2. Banner

`GET /banners`

Banner de la portada. Puede ser un **slider de imágenes** o **un solo video**, según lo elegido en el panel. Siempre responde `200`, aunque aún no haya banners (`slides` vacío).

### Respuesta `200`: slider de imágenes

```json
{
  "data": {
    "type": "slider",
    "screen_percentage": 90,
    "show_arrows": true,
    "show_indicators": true,
    "slides": [
      {
        "type": "image",
        "url": "https://admin.tuempresa.com/storage/files/2026/10/portada-1.jpg",
        "embed_url": null,
        "thumbnail_url": null,
        "title": "Diseñamos espacios que inspiran",
        "description": "Arquitectura residencial y comercial en Lima.",
        "link_url": "/proyectos"
      },
      {
        "type": "image",
        "url": "https://sitio.com/portada-2.jpg",
        "embed_url": null,
        "thumbnail_url": null,
        "title": null,
        "description": null,
        "link_url": null
      }
    ]
  }
}
```

### Respuesta `200`: video de YouTube

```json
{
  "data": {
    "type": "video",
    "screen_percentage": 90,
    "show_arrows": false,
    "show_indicators": false,
    "slides": [
      {
        "type": "youtube",
        "url": null,
        "embed_url": "https://www.youtube.com/embed/dQw4w9WgXcQ",
        "thumbnail_url": "https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg",
        "title": null,
        "description": null,
        "link_url": null
      }
    ]
  }
}
```

### Respuesta `200`: video de archivo

```json
{
  "data": {
    "type": "video",
    "screen_percentage": 90,
    "show_arrows": false,
    "show_indicators": false,
    "slides": [
      {
        "type": "video",
        "url": "https://admin.tuempresa.com/storage/files/2026/10/portada.mp4",
        "embed_url": null,
        "thumbnail_url": null,
        "title": null,
        "description": null,
        "link_url": null
      }
    ]
  }
}
```

### Respuesta `200`: sin configurar

```json
{
  "data": {
    "type": "slider",
    "screen_percentage": 100,
    "show_arrows": true,
    "show_indicators": true,
    "slides": []
  }
}
```

| Campo | Tipo | Descripción |
|---|---|---|
| `type` | string | `slider` (varias imágenes) o `video` (un solo video) |
| `screen_percentage` | integer | Porcentaje de la **altura de la pantalla** que ocupa el banner (10 a 100). Ej.: `90` → `height: 90vh` |
| `show_arrows` | boolean | Mostrar flechas. Siempre `false` si `type` es `video` |
| `show_indicators` | boolean | Mostrar indicadores (puntos). Siempre `false` si `type` es `video` |
| `slides` | array | Banners en el orden en que se deben mostrar |
| `slides[].type` | string | `image`, `video` (archivo) o `youtube` |
| `slides[].url` | string \| null | URL de la imagen o del video. `null` si es YouTube |
| `slides[].embed_url` | string \| null | Solo YouTube: URL para usar en un `<iframe>` |
| `slides[].thumbnail_url` | string \| null | Solo YouTube: miniatura del video |
| `slides[].title` | string \| null | Texto del banner. Solo en `slider` con `show_indicators: true` |
| `slides[].description` | string \| null | Descripción del banner. Solo en `slider` con `show_indicators: true` |
| `slides[].link_url` | string \| null | Enlace al hacer clic. Puede ser una URL completa (`https://...`), una ruta (`/proyectos`) o un ancla (`#contacto`) |

- Si `type` es `slider`, todos los elementos de `slides` son imágenes.
- Si `type` es `video`, `slides` trae **un solo** elemento, de tipo `video` o `youtube`.

---

## 3. Pop-up

`GET /popup`

Ventana emergente que se muestra al entrar al sitio. Cuando **no hay nada que mostrar** (no existe, está desactivada o no tiene contenido) responde `200` con `data: null`: en ese caso el sitio no debe mostrar nada.

### Respuesta `200`: slider de imágenes

```json
{
  "data": {
    "type": "slider",
    "title": "Promoción de aniversario",
    "show_header": true,
    "show_border": true,
    "link_url": "https://voladizo.pe/promo",
    "items": [
      {
        "kind": "image",
        "url": "https://admin.tuempresa.com/storage/files/2026/10/portada-1.jpg",
        "embed_url": null,
        "thumbnail_url": null
      },
      {
        "kind": "image",
        "url": "https://sitio.com/promo-2.jpg",
        "embed_url": null,
        "thumbnail_url": null
      }
    ],
    "updated_at": "2026-10-07T15:14:52+00:00"
  }
}
```

### Respuesta `200`: video

```json
{
  "data": {
    "type": "video",
    "title": null,
    "show_header": false,
    "show_border": true,
    "link_url": "https://voladizo.pe/promo",
    "items": [
      {
        "kind": "youtube",
        "url": null,
        "embed_url": "https://www.youtube.com/embed/dQw4w9WgXcQ",
        "thumbnail_url": "https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg"
      }
    ],
    "updated_at": "2026-10-07T15:14:52+00:00"
  }
}
```

### Respuesta `200`: sin pop-up

```json
{
  "data": null
}
```

| Campo | Tipo | Descripción |
|---|---|---|
| `type` | string | `image` (una imagen), `slider` (varias imágenes) o `video` |
| `title` | string \| null | Título para el encabezado. Solo llega si `show_header` es `true` |
| `show_header` | boolean | Mostrar un encabezado con el título y el botón de cerrar |
| `show_border` | boolean | Dejar un margen (borde) alrededor del contenido |
| `link_url` | string \| null | Enlace al hacer clic en el contenido |
| `items` | array | Contenido. Una imagen o un video en `image` / `video`; varias imágenes en `slider` |
| `items[].kind` | string | `image`, `video` (archivo) o `youtube` |
| `items[].url` | string \| null | URL de la imagen o del video. `null` si es YouTube |
| `items[].embed_url` | string \| null | Solo YouTube: URL para el `<iframe>` |
| `items[].thumbnail_url` | string \| null | Solo YouTube: miniatura |
| `updated_at` | string | Cambia cada vez que se guarda el pop-up |

> **Recomendación:** guarda `updated_at` en `localStorage` cuando el visitante cierre el pop-up, y vuelve a mostrarlo solo si `updated_at` es distinto. Así no se repite a quien ya lo cerró, pero se vuelve a mostrar cuando el cliente publica uno nuevo (ver [ejemplo](#pop-up-que-no-se-repite)).

---

## 4. Proyectos

### 4.1 Listado

`GET /projects`

Proyectos visibles, del más reciente al más antiguo, paginados. Trae lo necesario para armar las tarjetas; para la descripción completa usa el [detalle](#42-detalle).

| Parámetro | Tipo | Por defecto | Descripción |
|---|---|---|---|
| `page` | integer | `1` | Número de página |
| `per_page` | integer | `12` | Proyectos por página (mínimo 1, máximo 50) |
| `category` | string | | Filtra por categoría usando su `slug` (ver [categorías](#5-categorías-de-proyectos)). Un slug que no existe devuelve una lista vacía |

Ejemplo: `GET /projects?category=residencial&per_page=6&page=1`

#### Respuesta `200`

```json
{
  "data": [
    {
      "slug": "diseno-edificio-multifamiliar",
      "title": "Diseño Edificio Multifamiliar",
      "category": "Residencial",
      "category_slug": "residencial",
      "location": "San Borja, Lima",
      "execution_percentage": 100,
      "year": 2024,
      "cover_url": "https://admin.tuempresa.com/storage/files/2026/10/portada-1.jpg",
      "excerpt": "Desarrollo y modelado 3D de un edificio de 8 pisos. Área Pisos 1 200 m² 8"
    }
  ],
  "links": {
    "first": "https://admin.tuempresa.com/api/v1/projects?per_page=6&page=1",
    "last": "https://admin.tuempresa.com/api/v1/projects?per_page=6&page=1",
    "prev": null,
    "next": null
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 1,
    "path": "https://admin.tuempresa.com/api/v1/projects",
    "per_page": 6,
    "to": 1,
    "total": 1
  }
}
```

| Campo | Tipo | Descripción |
|---|---|---|
| `data[].slug` | string | Identificador para la URL. Se usa en el [detalle](#42-detalle) |
| `data[].title` | string | Título |
| `data[].category` | string \| null | Nombre de la categoría |
| `data[].category_slug` | string \| null | Slug de la categoría |
| `data[].location` | string \| null | Locación |
| `data[].execution_percentage` | integer \| null | Porcentaje de ejecución (0 a 100) |
| `data[].year` | integer \| null | Año |
| `data[].cover_url` | string \| null | Portada: la imagen de la primera fase |
| `data[].excerpt` | string | Resumen en texto plano de la descripción (sin HTML) |
| `links` | object | URLs de la primera, última, anterior y siguiente página (`null` si no existen) |
| `meta` | object | Datos de paginación: `current_page`, `last_page`, `per_page`, `total`, `from`, `to`. (También trae `path` y `links`, un listado de páginas ya armado, que no hace falta usar.) |

### 4.2 Detalle

`GET /projects/{slug}`

Un proyecto visible, buscado por su `slug`. Incluye la descripción completa, las fases y el video.

Ejemplo: `GET /projects/diseno-edificio-multifamiliar`

#### Respuesta `200`

```json
{
  "data": {
    "slug": "diseno-edificio-multifamiliar",
    "title": "Diseño Edificio Multifamiliar",
    "category": "Residencial",
    "category_slug": "residencial",
    "location": "San Borja, Lima",
    "execution_percentage": 100,
    "year": 2024,
    "cover_url": "https://admin.tuempresa.com/storage/files/2026/10/portada-1.jpg",
    "excerpt": "Desarrollo y modelado 3D de un edificio de 8 pisos. Área Pisos 1 200 m² 8",
    "description": "<p>Desarrollo y modelado <strong>3D</strong> de un edificio de 8 pisos.</p><table><tbody><tr><th><p>Área</p></th><th><p>Pisos</p></th></tr><tr><td><p>1 200 m²</p></td><td><p>8</p></td></tr></tbody></table>",
    "phases": [
      {
        "image_url": "https://admin.tuempresa.com/storage/files/2026/10/portada-1.jpg",
        "title": "Fase 1: Diseño",
        "content": "<p style=\"text-align: center;\">Concepto y volumetría.</p>"
      },
      {
        "image_url": "https://sitio.com/fase-2.jpg",
        "title": "Fase 2: Render",
        "content": "<p>Visualización final.</p>"
      }
    ],
    "video": {
      "type": "youtube",
      "url": null,
      "embed_url": "https://www.youtube.com/embed/dQw4w9WgXcQ",
      "thumbnail_url": "https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg"
    },
    "created_at": "2026-10-07T15:14:52+00:00",
    "updated_at": "2026-10-07T15:14:52+00:00"
  }
}
```

Además de los campos del listado:

| Campo | Tipo | Descripción |
|---|---|---|
| `description` | string \| null | Descripción completa en HTML (puede incluir tablas) |
| `phases` | array | Fases del proyecto, en orden |
| `phases[].image_url` | string \| null | Imagen de la fase |
| `phases[].title` | string \| null | Título de la fase |
| `phases[].content` | string \| null | Contenido de la fase en HTML |
| `video` | object \| null | Video del proyecto, o `null` si no tiene |
| `video.type` | string | `video` (archivo) o `youtube` |
| `video.url` | string \| null | URL del archivo. `null` si es YouTube |
| `video.embed_url` | string \| null | Solo YouTube: URL para el `<iframe>` |
| `video.thumbnail_url` | string \| null | Solo YouTube: miniatura |
| `created_at`, `updated_at` | string | Fechas de creación y modificación |

#### Respuesta `404`

El proyecto no existe o está oculto.

```json
{
  "message": "Proyecto no encontrado."
}
```

---

## 5. Categorías de proyectos

`GET /project-categories`

Categorías que tienen **al menos un proyecto visible**, en orden alfabético. Sirve para armar los filtros del listado de proyectos.

### Respuesta `200`

```json
{
  "data": [
    {
      "name": "Residencial",
      "slug": "residencial",
      "projects_count": 1
    }
  ]
}
```

| Campo | Tipo | Descripción |
|---|---|---|
| `name` | string | Nombre para mostrar |
| `slug` | string | Valor para el parámetro `category` de `/projects` |
| `projects_count` | integer | Cantidad de proyectos visibles de esa categoría |

Si ninguna categoría tiene proyectos visibles, `data` es una lista vacía (`[]`).

---

## 6. Testimonios

`GET /testimonials`

Testimonios visibles, del más reciente al más antiguo. Sin paginación.

### Respuesta `200`

```json
{
  "data": [
    {
      "name": "María Fernández",
      "role": "Gerente de Inmobiliaria Sol",
      "content": "Muy profesionales y puntuales.\nLos recomiendo totalmente.",
      "image_url": "https://admin.tuempresa.com/storage/files/2026/10/maria.jpg"
    }
  ]
}
```

| Campo | Tipo | Descripción |
|---|---|---|
| `name` | string | Nombre de quien da el testimonio |
| `role` | string \| null | Cargo |
| `content` | string | Testimonio en texto plano (puede traer saltos de línea `\n`) |
| `image_url` | string \| null | Imagen. `null` si no tiene (se sugiere mostrar un avatar con la inicial) |

Si no hay testimonios visibles, `data` es una lista vacía (`[]`).

---

## 7. Portadas

Imagen de cabecera de las páginas internas del sitio (Nosotros, Servicios, Contacto...). Cada página se identifica con una clave (`page`), definida en `config/covers.php` del panel. Solo se publican las portadas **activas** y que tienen imagen.

### 7.1 Listado

`GET /covers`

Todas las portadas activas, en el orden en que están configuradas las páginas. Sirve para cargarlas todas de una vez.

#### Respuesta `200`

```json
{
  "data": [
    {
      "page": "nosotros",
      "title": "Conoce nuestro estudio",
      "image_url": "https://admin.tuempresa.com/storage/files/2026/10/portada-nosotros.jpg"
    },
    {
      "page": "contacto",
      "title": null,
      "image_url": "https://sitio.com/portada-contacto.jpg"
    }
  ]
}
```

Si no hay portadas activas, `data` es una lista vacía (`[]`).

### 7.2 Portada de una página

`GET /covers/{page}`

Portada de una página interna, buscada por su clave. Cuando **no hay nada que mostrar** (la página no tiene portada, está inactiva, se quedó sin imagen o la clave no existe) responde `200` con `data: null`: en ese caso el sitio debe usar su portada por defecto.

Ejemplo: `GET /covers/nosotros`

#### Respuesta `200`

```json
{
  "data": {
    "page": "nosotros",
    "title": "Conoce nuestro estudio",
    "image_url": "https://admin.tuempresa.com/storage/files/2026/10/portada-nosotros.jpg"
  }
}
```

#### Respuesta `200`: sin portada

```json
{
  "data": null
}
```

| Campo | Tipo | Descripción |
|---|---|---|
| `page` | string | Clave de la página interna (`nosotros`, `servicios`, `proyectos`, `contacto`...) |
| `title` | string \| null | Título que se muestra sobre la portada. Texto plano |
| `image_url` | string | URL absoluta de la imagen. Tamaño recomendado: 1920x400 píxeles |

---

## Errores

Todos los errores son JSON con un campo `message`. (En un servidor de desarrollo con `APP_DEBUG=true` la respuesta trae además datos técnicos como `exception` y `trace`; en producción solo llega `message`.)

| Código | Cuándo | Respuesta |
|---|---|---|
| `404` | El recurso no existe o está oculto (`/company` sin configurar, `/projects/{slug}`) | `{ "message": "Proyecto no encontrado." }` |
| `405` | Se usó un método distinto de `GET` | `{ "message": "The POST method is not supported for route api/v1/projects. Supported methods: GET, HEAD." }` |
| `429` | Más de 60 peticiones por minuto desde la misma IP | `{ "message": "Too Many Attempts." }`. Trae `Retry-After` con los segundos de espera |
| `500` | Error del servidor | `{ "message": "Server Error" }` |

---

## Ejemplos de uso

### Consultar la API

```js
const API = 'https://admin.tuempresa.com/api/v1';

async function api(path) {
    const response = await fetch(`${API}${path}`, { headers: { Accept: 'application/json' } });

    if (!response.ok) {
        throw new Error(`Error ${response.status} en ${path}`);
    }

    return (await response.json()).data ?? null;
}
```

### Lista de proyectos con filtro por categoría

```js
const categories = await api('/project-categories');
const { data: projects } = await (await fetch(`${API}/projects?category=residencial&per_page=9`)).json();

projects.forEach((project) => {
    console.log(project.title, project.category, project.cover_url);
});
```

### Detalle de un proyecto

```js
const project = await api('/projects/diseno-edificio-multifamiliar');

document.querySelector('#descripcion').innerHTML = project.description; // HTML ya limpio

project.phases.forEach((phase) => {
    // phase.image_url, phase.title y phase.content (HTML)
});

if (project.video?.type === 'youtube') {
    iframe.src = project.video.embed_url;
} else if (project.video?.type === 'video') {
    video.src = project.video.url;
}
```

### Banner de la portada

```js
const banner = await api('/banners');

hero.style.height = `${banner.screen_percentage}vh`;

if (banner.type === 'video') {
    const [video] = banner.slides;
    // video.type === 'youtube' → <iframe src="{video.embed_url}">; si no → <video src="{video.url}">
} else {
    // Carrusel con banner.slides; flechas si banner.show_arrows; puntos si banner.show_indicators.
    // title y description solo vienen con show_indicators: true.
}
```

### Pop-up que no se repite

```js
const popup = await api('/popup');
const seen = localStorage.getItem('popup-visto');

if (popup && seen !== popup.updated_at) {
    showPopup(popup); // al cerrarlo:
    // localStorage.setItem('popup-visto', popup.updated_at);
}
```

### Testimonios

```js
const testimonials = await api('/testimonials');

testimonials.forEach((item) => {
    const quote = document.createElement('blockquote');
    quote.textContent = item.content; // texto plano: no usar innerHTML
    quote.style.whiteSpace = 'pre-line';
});
```

### Portada de una página interna

```js
const cover = await api('/covers/nosotros');

if (cover) {
    hero.style.backgroundImage = `url("${cover.image_url}")`;
    heroTitle.textContent = cover.title ?? ''; // texto plano: no usar innerHTML
}
// Si cover es null, se deja la portada por defecto del sitio.
```
