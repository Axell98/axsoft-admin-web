<?php

/*
|--------------------------------------------------------------------------
| Portadas de las páginas internas
|--------------------------------------------------------------------------
|
| pages son las páginas internas del sitio web del cliente que llevan una
| portada: clave => nombre que se muestra en el panel. La clave es la que usa
| el sitio para pedir la portada: /api/v1/covers/{clave}. Cada cliente tiene
| sus propias páginas.
|
| ecommended_size es el tamaño sugerido de la imagen (ancho x alto en
| píxeles). Solo es informativo: también define la proporción de la vista previa.
|
*/

return [

    'pages' => [
        'quienes-somos' => 'Quiénes Somos (/quienes-somos)',
        'diseno-e-ingenieria' => 'Diseño e Ingeniería (/servicios/diseno-e-ingenieria)',
        'construccion-y-remodelacion' => 'Construcción y Remodelación (/servicios/construccion-y-remodelacion)',
        'diseno-interior-y-mobiliario' => 'Diseño Interior y Mobiliario (/servicios/diseno-interior-y-mobiliario)',
        'gestion-y-supervision' => 'Gestión y Supervisión (/servicios/gestion-y-supervision)',
        'proyectos' => 'Proyectos (/proyectos)',
        'contacto' => 'Contacto (/contacto)',
    ],

    'recommended_size' => [
        'width' => 1920,
        'height' => 520,
    ],

];
