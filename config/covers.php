<?php

/*
|--------------------------------------------------------------------------
| Portadas de las páginas internas
|--------------------------------------------------------------------------
|
| `pages` son las páginas internas del sitio web del cliente que llevan una
| portada: clave => nombre que se muestra en el panel. La clave es la que usa
| el sitio para pedir la portada: /api/v1/covers/{clave}. Cada cliente tiene
| sus propias páginas.
|
| `recommended_size` es el tamaño sugerido de la imagen (ancho x alto en
| píxeles). Solo es informativo: también define la proporción de la vista previa.
|
*/

return [

    'pages' => [
        'nosotros' => 'Nosotros',
        'servicios' => 'Servicios',
        'proyectos' => 'Proyectos',
        'contacto' => 'Contacto',
    ],

    'recommended_size' => [
        'width' => 1920,
        'height' => 400,
    ],

];
